<?php

use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $adminRole = Rol::firstOrCreate(['name' => 'Administrador']);
    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->user = User::factory()->create();
});

function resourceImportXlsx(array $rows, array $headers = ['Nombre', 'Categoría', 'Descripción', 'Cantidad total']): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headers, null, 'A1');

    foreach ($rows as $index => $row) {
        $sheet->fromArray([$row], null, 'A'.($index + 2));
    }

    $path = tempnam(sys_get_temp_dir(), 'gestion-resource-import-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function resourceImportUpload(string $path): UploadedFile
{
    return new UploadedFile(
        $path,
        'implementos.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        UPLOAD_ERR_OK,
        true
    );
}

function resourceImportTokenFromResponse($response): string
{
    $location = $response->headers->get('Location');
    parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);
    expect($query)->toHaveKey('previsualizacion');

    return $query['previsualizacion'];
}

test('resource categories are restricted to the three official values', function () {
    expect(Recurso::CATEGORIAS)->toBe(['Deportivos', 'Audiovisuales', 'Otros']);

    $this->actingAs($this->admin)
        ->get(route('admin.recursos.create'))
        ->assertOk()
        ->assertSee('Deportivos')
        ->assertSee('Audiovisuales')
        ->assertSee('Otros')
        ->assertDontSee('Mobiliario')
        ->assertDontSee('Tecnología');

    $this->post(route('admin.recursos.store'), [
        'name' => 'Sillas',
        'tipo' => 'Mobiliario',
        'cantidad_total' => 10,
    ])->assertSessionHasErrors('tipo');

    $this->post(route('admin.recursos.store'), [
        'name' => 'Micrófono',
        'tipo' => 'Audiovisuales',
        'cantidad_total' => 3,
    ])->assertRedirect(route('admin.recursos.index'));
});

test('resource seeder creates only official categories and normalizes legacy inventory safely', function () {
    $this->seed(\Database\Seeders\RecursoSeeder::class);

    expect(Recurso::query()->distinct()->pluck('tipo')->all())
        ->each->toBeIn(Recurso::CATEGORIAS);

    $legacyTypes = [
        'Deportivo' => 'Deportivos',
        'Audiovisual' => 'Audiovisuales',
        'Mobiliario' => 'Otros',
        'Tecnología' => 'Otros',
    ];
    foreach (array_keys($legacyTypes) as $oldType) {
        Recurso::create([
            'name' => "Implemento legacy {$oldType}",
            'tipo' => $oldType,
            'cantidad_total' => 4,
            'cantidad_disponible' => 2,
        ]);
    }

    $migration = require database_path('migrations/2026_10_28_040000_normalize_resource_categories.php');
    $migration->up();

    foreach ($legacyTypes as $oldType => $newType) {
        $resource = Recurso::query()->where('name', "Implemento legacy {$oldType}")->firstOrFail();
        expect($resource->tipo)->toBe($newType)
            ->and($resource->cantidad_total)->toBe(4)
            ->and($resource->cantidad_disponible)->toBe(2);
    }
    expect(Recurso::query()->whereNotIn('tipo', Recurso::CATEGORIAS)->count())->toBe(0);
});

test('official Excel template has the real fields and exact category validation', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.recursos.plantilla'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $path = tempnam(sys_get_temp_dir(), 'gestion-resource-template-');
    file_put_contents($path, $response->streamedContent());
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getSheetByName('Implementos');

    expect($sheet->rangeToArray('A1:D1')[0])
        ->toBe(['Nombre', 'Categoría', 'Descripción', 'Cantidad total'])
        ->and($sheet->getCell('B2')->getDataValidation()->getFormula1())
        ->toBe('"Deportivos,Audiovisuales,Otros"')
        ->and($spreadsheet->getSheetByName('Instrucciones')->getCell('A7')->getValue())
        ->toContain('disponible');

    $spreadsheet->disconnectWorksheets();
    unlink($path);
});

test('valid Excel preview can be confirmed once and creates resources with initial stock', function () {
    $path = resourceImportXlsx([
        ['Proyector Epson', 'Audiovisuales', 'Salón de actos', 5],
        ['Balón de fútbol', 'Deportivos', '', 20],
        ['Mesa plegable', 'Otros', 'Depósito', 8],
    ]);

    try {
        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($path),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $token = resourceImportTokenFromResponse($previewResponse);

        $this->get(route('admin.recursos.importar', ['previsualizacion' => $token]))
            ->assertOk()
            ->assertSee('3 válidas')
            ->assertSee('0 con errores')
            ->assertSee('Proyector Epson')
            ->assertSee('Confirmar importación');

        $this->assertDatabaseCount('recursos', 0);
        $this->post(route('admin.recursos.importar.confirmar'), [
            'previsualizacion' => $token,
        ])->assertRedirect(route('admin.recursos.index'));

        expect(Recurso::count())->toBe(3)
            ->and(Recurso::where('name', 'Proyector Epson')->firstOrFail()->cantidad_disponible)->toBe(5)
            ->and(Recurso::where('name', 'Balón de fútbol')->firstOrFail()->tipo)->toBe('Deportivos')
            ->and(Recurso::query()->whereNotIn('tipo', Recurso::CATEGORIAS)->count())->toBe(0);

        $this->post(route('admin.recursos.importar.confirmar'), [
            'previsualizacion' => $token,
        ])->assertSessionHas('error');
    } finally {
        unlink($path);
    }
});

test('invalid rows show row and field errors and cannot be imported', function () {
    $path = resourceImportXlsx([
        ['Proyector', 'Audiovisuales', 'Auditorio', 2],
        ['Silla', 'Mobiliario', '', 4],
        ['Balón sin cantidad', 'Deportivos', '', null],
        ['Balón duplicado', 'Deportivos', '', 1],
        ['Balón duplicado', 'Deportivos', '', 2],
    ]);

    try {
        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($path),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $token = resourceImportTokenFromResponse($previewResponse);

        $this->get(route('admin.recursos.importar', ['previsualizacion' => $token]))
            ->assertOk()
            ->assertSee('1 válidas')
            ->assertSee('4 con errores')
            ->assertSee('Fila')
            ->assertSee('Categoría:')
            ->assertSee('Usa únicamente Deportivos, Audiovisuales u Otros.')
            ->assertSee('Este nombre está duplicado en el archivo.')
            ->assertSee('Cantidad total:')
            ->assertDontSee('Confirmar importación');

        $this->assertDatabaseCount('recursos', 0);
        $this->post(route('admin.recursos.importar.confirmar'), [
            'previsualizacion' => $token,
        ])->assertRedirect(route('admin.recursos.importar', ['previsualizacion' => $token]))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('recursos', 0);
    } finally {
        unlink($path);
    }
});

test('import validates the workbook structure and rejects malformed files', function () {
    $wrongHeaders = resourceImportXlsx([
        ['Balón', 'Deportivos', '', 3],
    ], ['Nombre', 'Tipo', 'Descripción', 'Cantidad total']);
    $malformed = tempnam(sys_get_temp_dir(), 'gestion-resource-invalid-');
    file_put_contents($malformed, 'not an Excel document');

    try {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($wrongHeaders),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $token = resourceImportTokenFromResponse($response);
        $this->get(route('admin.recursos.importar', ['previsualizacion' => $token]))
            ->assertSee('Las columnas deben ser exactamente');

        $this->from(route('admin.recursos.importar'))
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($malformed),
            ])->assertRedirect(route('admin.recursos.importar'))
            ->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('recursos', 0);
    } finally {
        unlink($wrongHeaders);
        unlink($malformed);
    }
});

test('import preview rejects formula cells and names already present in inventory', function () {
    Recurso::create([
        'name' => 'Proyector existente',
        'tipo' => 'Audiovisuales',
        'cantidad_total' => 1,
        'cantidad_disponible' => 1,
    ]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Nombre', 'Categoría', 'Descripción', 'Cantidad total'], null, 'A1');
    $sheet->fromArray(['Proyector existente', 'Audiovisuales', '', 2], null, 'A2');
    $sheet->fromArray(['Balón calculado', 'Deportivos', '', null], null, 'A3');
    $sheet->getCell('D3')->setValueExplicit('=1+1', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);
    $path = tempnam(sys_get_temp_dir(), 'gestion-resource-formula-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    try {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($path),
            ])
            ->assertSessionHasNoErrors();
        $token = resourceImportTokenFromResponse($response);

        $this->get(route('admin.recursos.importar', ['previsualizacion' => $token]))
            ->assertOk()
            ->assertSee('Ya existe un implemento con este nombre.')
            ->assertSee('No se permiten fórmulas en la plantilla.');

        $this->assertDatabaseCount('recursos', 1);
    } finally {
        unlink($path);
    }
});

test('an import failure rolls back every inserted batch', function () {
    $rows = [];
    for ($index = 1; $index <= 101; $index++) {
        $rows[] = ["Implemento {$index}", 'Otros', '', 1];
    }
    $path = resourceImportXlsx($rows);

    try {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.recursos.importar.previsualizar'), [
                'archivo' => resourceImportUpload($path),
            ])
            ->assertSessionHasNoErrors();
        $token = resourceImportTokenFromResponse($response);

        DB::statement("
            CREATE TRIGGER fail_resource_import
            BEFORE INSERT ON recursos
            WHEN NEW.name = 'Implemento 101'
            BEGIN
                SELECT RAISE(ABORT, 'Simulated import failure');
            END
        ");

        $this->post(route('admin.recursos.importar.confirmar'), [
            'previsualizacion' => $token,
        ])->assertInternalServerError();

        $this->assertDatabaseCount('recursos', 0);
    } finally {
        unlink($path);
    }
});

test('only administrators can access template, preview, and confirmation routes', function () {
    $path = resourceImportXlsx([
        ['Pelota', 'Deportivos', '', 2],
    ]);

    try {
        $this->actingAs($this->user)
            ->get(route('admin.recursos.importar'))
            ->assertForbidden();
        $this->get(route('admin.recursos.plantilla'))->assertForbidden();
        $this->post(route('admin.recursos.importar.previsualizar'), [
            'archivo' => resourceImportUpload($path),
        ])->assertForbidden();
        $this->post(route('admin.recursos.importar.confirmar'), [
            'previsualizacion' => str_repeat('a', 64),
        ])->assertForbidden();
    } finally {
        unlink($path);
    }
});

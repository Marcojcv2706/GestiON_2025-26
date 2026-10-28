<?php

namespace App\Services;

use App\Models\Recurso;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecursoExcelImportService
{
    private const HEADERS = [
        'Nombre',
        'Categoría',
        'Descripción',
        'Cantidad total',
    ];

    private const MAX_ROWS = 5000;

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Implementos');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:D1')->getFill()->setFillType('solid')->getStartColor()->setRGB('003366');
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(48);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->freezePane('A2');

        $categoryValidation = $sheet->getCell('B2')->getDataValidation();
        $categoryValidation->setType(DataValidation::TYPE_LIST);
        $categoryValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $categoryValidation->setAllowBlank(false);
        $categoryValidation->setShowInputMessage(true);
        $categoryValidation->setShowErrorMessage(true);
        $categoryValidation->setErrorTitle('Categoría no permitida');
        $categoryValidation->setError('Elige Deportivos, Audiovisuales u Otros.');
        $categoryValidation->setPromptTitle('Categoría');
        $categoryValidation->setPrompt('Elige una de las tres categorías oficiales.');
        $categoryValidation->setFormula1('"Deportivos,Audiovisuales,Otros"');
        $categoryValidation->setSqref('B2:B5001');

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instrucciones');
        $instructions->fromArray([
            ['Plantilla de importación de implementos'],
            ['Completa la hoja Implementos y conserva los nombres de las columnas.'],
            ['Categoría: usa únicamente Deportivos, Audiovisuales u Otros.'],
            ['Cantidad total: debe ser un número entero mayor que cero.'],
            ['Nombre: debe ser único y no existir previamente en el inventario.'],
            ['Descripción: campo opcional (máximo 1000 caracteres).'],
            ['La cantidad disponible se inicializa igual a la cantidad total.'],
            ['El estado se calcula automáticamente según las unidades disponibles.'],
        ], null, 'A1');
        $instructions->getColumnDimension('A')->setWidth(100);
        $instructions->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $response = response()->streamDownload(static function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'plantilla-implementos-gestion.xlsx');
        $response->headers->set(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        return $response;
    }

    public function preview(string $path): array
    {
        try {
            $readerType = IOFactory::identify($path);
        } catch (ReaderException|SpreadsheetException) {
            throw ValidationException::withMessages([
                'archivo' => 'No se pudo leer el archivo. Sube una plantilla Excel .xlsx o .xls válida.',
            ]);
        }

        if (! in_array($readerType, ['Xlsx', 'Xls'], true)) {
            throw ValidationException::withMessages([
                'archivo' => 'El contenido del archivo no corresponde a un Excel .xlsx o .xls válido.',
            ]);
        }

        $spreadsheet = $this->loadSpreadsheet($path, $readerType);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $sheet->getHighestDataColumn()
        );

        if ($highestRow > self::MAX_ROWS + 1) {
            $spreadsheet->disconnectWorksheets();
            throw ValidationException::withMessages([
                'archivo' => 'El archivo no puede contener más de '.self::MAX_ROWS.' filas de datos.',
            ]);
        }

        $header = [];
        for ($column = 1; $column <= max(4, $highestColumnIndex); $column++) {
            $header[] = $this->cellText($sheet->getCell([$column, 1])->getValue());
        }
        $spreadsheet->disconnectWorksheets();

        $normalizedHeader = array_map(fn ($value) => mb_strtolower(trim($value)), $header);
        $expectedHeader = array_map('mb_strtolower', self::HEADERS);
        if ($highestColumnIndex !== count(self::HEADERS)
            || array_slice($normalizedHeader, 0, 4) !== $expectedHeader
            || count(array_filter(array_slice($normalizedHeader, 4), fn ($value) => $value !== '')) > 0) {
            return [
                'rows' => [],
                'valid_count' => 0,
                'error_count' => 0,
                'global_errors' => [
                    'Estructura' => 'Las columnas deben ser exactamente: '.implode(' · ', self::HEADERS).'.',
                ],
            ];
        }

        $spreadsheet = $this->loadSpreadsheet($path, $readerType);
        $sheet = $spreadsheet->getSheet(0);
        $rows = [];
        $candidateNames = [];
        $fields = ['name', 'tipo', 'descripcion', 'cantidad_total'];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $raw = [];
            $formulaFields = [];
            for ($column = 1; $column <= 4; $column++) {
                $cell = $sheet->getCell([$column, $rowNumber]);
                $raw[] = $cell->getValue();
                if ($cell->isFormula()) {
                    $formulaFields[] = $fields[$column - 1];
                }
            }

            if (count(array_filter($raw, fn ($value) => trim($this->cellText($value)) !== '')) === 0) {
                continue;
            }

            $data = [
                'name' => trim($this->cellText($raw[0])),
                'tipo' => trim($this->cellText($raw[1])),
                'descripcion' => trim($this->cellText($raw[2])),
                'cantidad_total' => $raw[3],
            ];
            $errors = $this->validateRow($data);
            foreach ($formulaFields as $field) {
                $errors[$field][] = 'No se permiten fórmulas en la plantilla.';
            }
            $rows[] = [
                'number' => $rowNumber,
                'data' => $data,
                'errors' => $errors,
            ];

            if (! isset($errors['name'])) {
                $candidateNames[mb_strtolower($data['name'])][] = count($rows) - 1;
            }
        }

        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            return [
                'rows' => [],
                'valid_count' => 0,
                'error_count' => 0,
                'global_errors' => ['Archivo' => 'El archivo no contiene filas con datos para importar.'],
            ];
        }

        foreach ($candidateNames as $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $index) {
                    $rows[$index]['errors']['name'] = 'Este nombre está duplicado en el archivo.';
                }
            }
        }

        $namesToCheck = collect($rows)
            ->filter(fn ($row) => ! isset($row['errors']['name']))
            ->pluck('data.name')
            ->unique()
            ->values();
        $existingNames = Recurso::query()
            ->whereIn('name', $namesToCheck)
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower($name))
            ->flip();

        foreach ($rows as &$row) {
            if (isset($existingNames[mb_strtolower($row['data']['name'])])) {
                $row['errors']['name'] = 'Ya existe un implemento con este nombre.';
            }
        }
        unset($row);

        $validCount = count(array_filter($rows, fn ($row) => $row['errors'] === []));

        return [
            'rows' => $rows,
            'valid_count' => $validCount,
            'error_count' => count($rows) - $validCount,
            'global_errors' => [],
        ];
    }

    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', Rule::in(Recurso::CATEGORIAS)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'cantidad_total' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ], [
            'tipo.in' => 'Usa únicamente Deportivos, Audiovisuales u Otros.',
            'cantidad_total.integer' => 'La cantidad total debe ser un número entero.',
            'cantidad_total.min' => 'La cantidad total debe ser mayor que cero.',
            'cantidad_total.max' => 'La cantidad total supera el máximo permitido.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return [];
    }

    private function cellText(mixed $value): string
    {
        if ($value === null || is_bool($value) || is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    private function loadSpreadsheet(string $path, string $readerType): Spreadsheet
    {
        try {
            return IOFactory::createReader($readerType)
                ->setReadDataOnly(true)
                ->load($path);
        } catch (ReaderException|SpreadsheetException) {
            throw ValidationException::withMessages([
                'archivo' => 'El Excel está dañado o no se puede leer. Verifica el archivo e inténtalo nuevamente.',
            ]);
        }
    }
}

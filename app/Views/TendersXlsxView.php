<?php

declare(strict_types=1);

namespace App\Views;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use ZipArchive;

/**
 * Genera el reporte Excel del listado de licitaciones.
 *
 * Un archivo .xlsx es un zip con documentos XML. Esta vista construye esa
 * estructura con ZipArchive y xmlwriter, sin dependencias externas, y devuelve
 * el binario listo para descargar.
 *
 * @author    Fernando Luis Gil Correa
 * @version   1.0.0
 * @since     2026-10-25
 * @updated   2026-10-25
 * @package   App\Views
 *
 * @change    1.0.0 - 2026-10-25 - Fernando Luis Gil Correa - Creación del generador del reporte Excel de licitaciones.
 */
class TendersXlsxView
{
    # Columnas del reporte, en el mismo orden que el listado.
    private const HEADERS = [
        'Consecutivo',
        'Objeto',
        'Descripción',
        'Fecha de inicio',
        'Fecha de cierre',
        'Estado'
    ];

    # Ancho de cada columna del reporte.
    private const WIDTHS = [16, 42, 55, 16, 16, 12];


    #Genera el reporte y retorna su contenido binario.
    public static function build(iterable $tenders): string
    {
        $rows = [];

        foreach ($tenders as $tender) {
            $rows[] = [
                (string) $tender->consecutive_code,
                (string) $tender->subject,
                (string) $tender->description,
                self::dateSerial($tender->start_date),
                self::dateSerial($tender->close_date),
                (string) $tender->status
            ];
        }

        $file = tempnam(sys_get_temp_dir(), 'reporte_');

        if ($file === false) {
            return '';
        }

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRelationships());
        $zip->addFromString('docProps/core.xml', self::coreProperties());
        $zip->addFromString('docProps/app.xml', self::appProperties());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheet($rows));
        $zip->close();

        $binary = file_get_contents($file);
        unlink($file);

        return is_string($binary) ? $binary : '';
    }

    # Nombre del archivo del reporte.
    public static function filename(): string
    {
        return 'licitaciones-' . date('Y-m-d') . '.xlsx';
    }

    /**
     * Convierte una fecha en el número de serie que usa Excel.
     *
     * Excel cuenta los días desde el 30 de diciembre de 1899; con ese
     * referencia una fecha se guarda como número y puede ordenarse y
     * filtrarse como fecha dentro de la hoja.
     */
    private static function dateSerial(mixed $date): ?int
    {
        if ($date instanceof DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }

        if (!is_string($date) || $date === '') {
            return null;
        }

        $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));

        if ($value === false) {
            return null;
        }

        $epoch = new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC'));

        return (int) floor(($value->getTimestamp() - $epoch->getTimestamp()) / 86400);
    }

    # Manifiesto de los tipos de contenido del paquete.
    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    # Relaciones del paquete con el libro y las propiedades.
    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    # Propiedades del documento.
    private static function coreProperties(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Licitaciones</dc:title>'
            . '<dc:creator>Suplos</dc:creator>'
            . '<cp:lastModifiedBy>Suplos</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    # Propiedades extendidas de la aplicacion.
    private static function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"'
            . ' xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Suplos API</Application>'
            . '<Company>Suplos</Company>'
            . '</Properties>';
    }

    # Libro de trabajo con una unica hoja.
    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Licitaciones" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    # Relaciones del libro con la hoja y los estilos.
    private static function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /**
     * Estilos de la hoja.
     *
     * El indice 0 es el estilo normal, el 1 el encabezado (negrita, letra
     * blanca sobre fondo oscuro y centrado) y el 2 el formato de fecha.
     */
    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="yyyy\\-mm\\-dd"/></numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * Hoja con los datos del reporte.
     *
     * @param array<int, array<int, mixed>> $rows
     */
    private static function worksheet(array $rows): string
    {
        $lastRow = count($rows) + 1;
        $lastColumn = self::columnName(count(self::HEADERS));

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A2" sqref="A2"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>';

        foreach (self::WIDTHS as $index => $width) {
            $sheet .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        $sheet .= '</cols><sheetData>';
        $sheet .= '<row r="1" ht="24" customHeight="1">';

        foreach (self::HEADERS as $index => $header) {
            $sheet .= self::inlineCell(self::columnName($index + 1) . '1', $header, 1);
        }

        $sheet .= '</row>';
        $row = 2;

        foreach ($rows as $values) {
            $sheet .= '<row r="' . $row . '">';

            foreach ($values as $index => $value) {
                $reference = self::columnName($index + 1) . $row;

                if (is_int($value)) {
                    $sheet .= '<c r="' . $reference . '" s="2"><v>' . $value . '</v></c>';
                    continue;
                }

                $sheet .= self::inlineCell($reference, (string) $value, 0);
            }

            $sheet .= '</row>';
            $row++;
        }

        $sheet .= '</sheetData><autoFilter ref="A1:' . $lastColumn . $lastRow . '"/></worksheet>';

        return $sheet;
    }

    # Celda de texto en linea (inline string).
    private static function inlineCell(string $reference, string $value, int $style): string
    {
        $text = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr">'
            . '<is><t xml:space="preserve">' . $text . '</t></is></c>';
    }

    # Letra de la columna a partir de su numero (1 = A, 2 = B, ...).
    private static function columnName(int $index): string
    {
        return chr(64 + $index);
    }
}

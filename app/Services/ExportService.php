<?php

namespace App\Services;

class ExportService
{
    /**
     * Neutralize spreadsheet formula injection for text values.
     */
    public static function sanitizeCell($value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string)$value;
        if (preg_match('/^[=\+\-@\t\r]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Generate standard RFC-4180 CSV with UTF-8 BOM so Excel opens it with proper Indian Rupee symbols and UTF-8 characters.
     */
    public static function toCsv(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        // UTF-8 BOM
        fputs($fp, "\xEF\xBB\xBF");

        // Write header
        $sanitizedHeaders = array_map([self::class, 'sanitizeCell'], $headers);
        fputcsv($fp, $sanitizedHeaders, ',', '"', "\\");

        // Write rows
        foreach ($rows as $row) {
            $sanitizedRow = [];
            foreach ($row as $val) {
                if (is_numeric($val) && !is_string($val)) {
                    $sanitizedRow[] = $val;
                } else {
                    $sanitizedRow[] = self::sanitizeCell($val);
                }
            }
            fputcsv($fp, $sanitizedRow, ',', '"', "\\");
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    /**
     * Generate Microsoft Excel XML Spreadsheet format (.xls) with typed numeric and string cells.
     */
    public static function toExcelXml(string $sheetTitle, array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        // Styles
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="Header"><Alignment ss:Vertical="Center" ss:Bold="1"/><Font ss:FontName="Calibri" ss:Size="11" ss:Color="#FFFFFF"/><Interior ss:Color="#4F46E5" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="Currency"><Alignment ss:Horizontal="Right"/><NumberFormat ss:Format="#,##0.00"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="Text"><NumberFormat ss:Format="@"/></Style>' . "\n";
        $xml .= ' </Styles>' . "\n";

        $safeTitle = htmlspecialchars(substr($sheetTitle, 0, 31), ENT_QUOTES, 'UTF-8');
        $xml .= ' <Worksheet ss:Name="' . $safeTitle . '">' . "\n";
        $xml .= '  <Table>' . "\n";

        // Header row
        $xml .= '   <Row ss:StyleID="Header">' . "\n";
        foreach ($headers as $h) {
            $xml .= '    <Cell><Data ss:Type="String">' . htmlspecialchars(self::sanitizeCell($h), ENT_QUOTES, 'UTF-8') . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";

        // Data rows
        foreach ($rows as $row) {
            $xml .= '   <Row>' . "\n";
            foreach ($row as $val) {
                if (is_numeric($val) && !is_string($val)) {
                    $xml .= '    <Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $val . '</Data></Cell>' . "\n";
                } else {
                    $xml .= '    <Cell ss:StyleID="Text"><Data ss:Type="String">' . htmlspecialchars(self::sanitizeCell($val), ENT_QUOTES, 'UTF-8') . '</Data></Cell>' . "\n";
                }
            }
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return $xml;
    }
}

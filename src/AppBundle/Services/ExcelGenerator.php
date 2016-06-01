<?php


namespace AppBundle\Services;


class ExcelGenerator
{
    protected $writter;

    protected $excel;

    public function __construct()
    {
        $excel = new \PHPExcel();
        $writer =  new \PHPExcel_Writer_Excel2007($excel);

        $this->writter = $writer;
        $this->excel = $excel;
    }

    public function generate(array $data, $fields, $name)
    {
        $values = [];

        //Pone el header en negrita
        $values[] = $fields;

        foreach($data as $row) {
            $values[] = array_values($row);
        }

        $this->excel
            ->getActiveSheet()
            ->fromArray($values)
        ;

        $name = __DIR__.'/../../../solicitudes/'.$name;

        $this->autosizeWorksheet($this->excel->getActiveSheet());
        $this->firstRowBold($this->excel->getActiveSheet(), $fields);

        $this->writter->save($name);

        return new \SplFileObject($name, 'r');
    }


    protected function autosizeWorksheet(\PHPExcel_Worksheet $sheet)
    {

        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        /** @var \PHPExcel_Cell $cell */
        foreach ($cellIterator as $cell) {
            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }
    }


    protected function firstRowBold(\PHPExcel_Worksheet $sheet, $header)
    {
        $first_letter = \PHPExcel_Cell::stringFromColumnIndex(0);
        $last_letter = \PHPExcel_Cell::stringFromColumnIndex(count($header)-1);
        $header_range = "{$first_letter}1:{$last_letter}1";
        $sheet->getStyle($header_range)->getFont()->setBold(true);
    }



} 
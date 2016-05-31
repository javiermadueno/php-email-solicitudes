<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 31/05/2016
 * Time: 13:00
 */

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



} 
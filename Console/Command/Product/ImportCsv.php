<?php
namespace liaonau\fastimport\Console\Command\Product;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\App\Filesystem\DirectoryList;
use liaonau\fastimport\Console\Command\AbstractImportCommand;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\ImportExport\Model\Import;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportCsv extends AbstractImportCommand
{
    const IMPORT_FILE = "import.csv";

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $readFactory;
    /**
     * @var DirectoryList
     */
    private $directory_list;

    /**
     * Constructor
     *
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(
        ObjectManagerFactory $objectManagerFactory,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directory_list
    )
    {

        parent::__construct($objectManagerFactory);


        $this->readFactory = $readFactory;

        $this->directory_list = $directory_list;
    }

    protected function configure()
    {

        $this->setName('fastimport:products:importcsv')
            ->setDescription('Import Products from CSV');
        $this->setBehavior(Import::BEHAVIOR_ADD_UPDATE);
        $this->setEntityCode('catalog_product');

        parent::configure();
    }

    /**
     * @return array
     */
    protected function getEntities()
    {
        $data = array();
        $csvIterationObject = $this->readCSV();
        // Do mapping here:
        echo ("Number of records: " . count($csvIterationObject). " Start from: " . (($this->page-1) * $this->pageSize+1). "\n");

        foreach($csvIterationObject as $row){
            $data[]  = $row;
        }
        //  Mapping end


        return $data;
    }

    protected function readCSV()
    {

            $csvObj = Reader::createFromString($this->readFile(static::IMPORT_FILE));
            $csvObj->setDelimiter('|');
            $csvObj->setHeaderOffset(0);

            $statement = (new Statement())
                ->offset(($this->page - 1) * $this->pageSize + 1)
                ->limit($this->pageSize);

            $result = $statement->process($csvObj);





        return $result;

    }

    protected function readFile($fileName)
    {
        $path = $this->directory_list->getRoot();
        $directoryRead = $this->readFactory->create($path);
        return $directoryRead->readFile($fileName);
    }
}

<?php


namespace liaonau\fastimport\Console\Command;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\State;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class AbstractImportCommand extends Command
{

    /**
     * @var string
     */
    protected $behavior;
    /**
     * @var string
     */
    protected $entityCode;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * Object manager factory
     *
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $pageSize = 10000;

    /**
     * Constructor
     *
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();
    }

    public function arrayToAttributeString($array)
    {


        $attributes_str = NULL;
        foreach ($array as $attribute => $value) {

            $attributes_str .= "$attribute=$value,";

        }

        return $attributes_str;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omParams = $_SERVER;
        $omParams[StoreManager::PARAM_RUN_CODE] = 'admin';
        $omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        $this->objectManager = $this->objectManagerFactory->create($omParams);

        $area = FrontNameResolver::AREA_CODE;

        /** @var \Magento\Framework\App\State $appState */
        $appState = $this->objectManager->get('Magento\Framework\App\State');
        $appState->setAreaCode($area);
        $configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
        $this->objectManager->configure($configLoader->load($area));

        $output->writeln('Import started');

        $time = microtime(true);

        /** @var \FireGento\FastSimpleImport\Model\Importer $importerModel */
        $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');

        $this->page = 1;

        while ($this->page > 0){
            $output->writeln("Page: " . $this->page);
            try{
            $productsArray = $this->getEntities();
            }	 catch(\Exception $e) {
                $output->writeln($e->getMessage());
            }
            $output->writeln("Records to import: " . count($productsArray));

            if (count($productsArray) < $this->pageSize){
                $this->page = 0;
            } else {
                $this->page++;
            }

            $importerModel->setBehavior($this->getBehavior());
            $importerModel->setEntityCode($this->getEntityCode());
            $adapterFactory = $this->objectManager->create('FireGento\FastSimpleImport\Model\Adapters\NestedArrayAdapterFactory');
            $importerModel->setImportAdapterFactory($adapterFactory);

            try {
                $len = count($productsArray);
                $step = 5000;
                $left = 0;

                if ($len > 0){

                    while ($left < $len){
                        if ($left + $step > $len){
                            $right = $len;
                        } else {
                            $right = $left + $step;
                        }

                        $output->writeln("left: " . $left . " Rigth: " . $right);
                        $importerModel->processImport(array_slice($productsArray,$left,$right));
                        $left = $left + $step;
                        $left++;
                    }

                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }

            $output->write($importerModel->getLogTrace());
            $output->write($importerModel->getErrorMessages());
        }

        $output->writeln('Import finished. Elapsed time: ' . round(microtime(true) - $time, 2) . 's' . "\n");
        $this->afterFinishImport();

    }

    /**
     * @return array
     */
    abstract protected function getEntities();

    /**
     * @return string
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->behavior = $behavior;
    }

    /**
     * @return string
     */
    public function getEntityCode()
    {
        return $this->entityCode;
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->entityCode = $entityCode;
    }


    public function afterFinishImport(){

    }

}

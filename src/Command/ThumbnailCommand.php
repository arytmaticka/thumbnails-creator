<?php

namespace App\Command;

use App\Service\ImageResizerInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'app:thumbnail',
    description: 'Add a short description for your command',
)]
class ThumbnailCommand extends Command
{
    const DEFAULT_STORAGE_TYPE = 'local';

    private ?SymfonyStyle $io;

    public function __construct(
        private LoggerInterface       $logger,
        private ParameterBagInterface $parameterBag,
        private FilesystemOperator    $localStorage,
        private FilesystemOperator    $amazonStorage,
        private FilesystemOperator    $dropboxStorage,
        private ImageResizerInterface $imageResizer,
        string                        $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'source',
                InputArgument::OPTIONAL,
                'Folder with photos',
                $this->parameterBag->get('photosFolder')
            )
            ->addOption('destinationType', 't', InputOption::VALUE_OPTIONAL, 'Target filesystem type')
            ->addOption('destination', 'd', InputOption::VALUE_OPTIONAL, 'Target folder', '')
            ->addOption('maxWidth', null, InputOption::VALUE_OPTIONAL, 'Maximal width', 150);
    }

    /**
     * List of available storage types
     *
     * @return string[]
     */
    protected function getStorageTypes(): array
    {
        return ['local', 'dropbox', 'amazon'];
    }

    /**
     * Choosing destination filesystem
     *
     * @param string|null $storageType Name of storage type
     * @return Filesystem
     */
    protected function chooseStorage(?string $storageType): Filesystem
    {
        if (!in_array($storageType, $this->getStorageTypes())) {
            if ($this->io instanceof SymfonyStyle) {
                $storageType = $this->io->choice(
                    'What destination would you like to use?',
                    $this->getStorageTypes(),
                    'local'
                );
            } else {
                $storageType = self::DEFAULT_STORAGE_TYPE;
            }
        }

        $storageName = $storageType . 'Storage';
        return $this->{$storageName};
    }

    /**
     * List files in folder
     *
     * @param string|null $folder folder name
     * @return array array of elements in folder
     */
    protected function listPhotos(?string $folder = null): array
    {
        //@todo Filter only images

        if (is_null($folder)) {
            $this->error('Wrong file directory');
            return [];
        }

        $ret = [];
        $finder = new Finder();
        $finder->files()->depth(0)->in($folder);
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $ret[] = $file;
            }
        }
        return $ret;
    }

    /**
     * @param string $msg Error message
     */
    protected function error(string $msg)
    {
        $this->logger->error($msg);
        if ($this->io instanceof SymfonyStyle) {
            $this->io->error($msg);
        }
    }

    /**
     * @param string $msg Info message
     */
    protected function info(string $msg)
    {
        $this->logger->info($msg);
        if ($this->io instanceof SymfonyStyle) {
            $this->io->writeln($msg);
        }
    }

    /**
     * Rescaling images to given maximal width
     *
     * @param Filesystem $filesystem Target destination
     * @param string $destination Destination folder od filesystem
     * @param array $paths Array of files to convert
     * @param int $maxWidth Maximal width of rescaled image
     */
    protected function rescaleImages(Filesystem $filesystem, string $destination, array $paths, int $maxWidth)
    {
        try {
            foreach ($paths as $file) {
                /** @var SplFileInfo $file */
                $this->info('Resizing: ' . $file->getRealPath(). ' to '.$destination.$file->getBasename());
                $img = $this->imageResizer->scaleMaxWidth($file->getRealPath(), $maxWidth);
                $filesystem->write($destination.$file->getBasename(), $img);
            }
        } catch (\Throwable $throwable) {
            $this->error($throwable->getMessage());
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $source = $input->getArgument('source');
        $destinationType = $input->getOption('destinationType');
        $destination = $input->getOption('destination');
        $maxWidth = $input->getOption('maxWidth');
        $photos = $this->listPhotos($source);
        $filesystem = $this->chooseStorage($destinationType);
        $this->rescaleImages($filesystem, $destination, $photos, $maxWidth);

        return Command::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace Blacksheep\Brand\Console\Command;

use Blacksheep\Brand\Model\ImageResize as ImageResize;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\MediaStorage\Service\ImageResizeScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImageResizeCommand extends Command
{
    private const ASYNC_RESIZE = 'async';

    public function __construct(
        private State $appState,
        private ImageResize $imageResize,
        private ImageResizeScheduler $imageResizeScheduler,
        private ProgressBarFactory $progressBarFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('brand:images:resize');
        $this->setDescription('Creates resized brand images');
        $this->setDefinition($this->getOptionsList());

        parent::configure();
    }

    private function getOptionsList() : array
    {
        return [
            new InputOption(
                self::ASYNC_RESIZE,
                'a',
                InputOption::VALUE_NONE,
                'Resize image in asynchronous mode'
            ),
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $input->getOption(self::ASYNC_RESIZE) ?
            $this->executeAsync($output) : $this->executeSync($output);
    }

    private function executeSync(OutputInterface $output): int
    {
        try {
            $errors = [];
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $generator = $this->imageResize->resizeFromThemes();
            $progress = $this->getProgressBar($output, $generator->current());

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            while ($generator->valid()) {
                $resizeInfo = $generator->key();
                $error = $resizeInfo['error'];
                $filename = $resizeInfo['filename'];

                if ($error !== '') {
                    $errors[$filename] = $error;
                }

                $progress->setMessage($filename);
                $progress->advance();
                $generator->next();
            }
        } catch (\Exception $e) {
            return $this->writeResponse($output, ["<error>{$e->getMessage()}</error>"]);
        }

        return $this->writeResponse($output, $errors);
    }

    private function executeAsync(OutputInterface $output): int
    {
        try {
            $errors = [];
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
            $progress = $this->getProgressBar($output, $this->imageResize->getCountBrandImages());

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            $brandImages = $this->imageResize->getBrandImages();
            foreach ($brandImages as $image) {
                $result = $this->imageResizeScheduler->schedule($image['filepath']);

                if (!$result) {
                    $errors[$image['filepath']] = 'Error image scheduling: ' . $image['filepath'];
                }
                $progress->setMessage($image['filepath']);
                $progress->advance();
            }
        } catch (\Exception $e) {
            return $this->writeResponse($output, ["<error>{$e->getMessage()}</error>"]);
        }

        return $this->writeResponse($output, $errors);
    }

    private function getProgressBar(OutputInterface $output, int $max): ProgressBar
    {
        /** @var ProgressBar $progress */
        $progress = $this->progressBarFactory->create([
            'output' => $output,
            'max' => $max,
        ]);
        $progress->setFormat(
            "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
        );

        return $progress;
    }

    private function writeResponse(OutputInterface $output, array $errors = []): int
    {
        $output->write(PHP_EOL);
        if (!$errors) {
            $output->writeln("<info>Brand images resized successfully</info>");

            return Cli::RETURN_SUCCESS;
        }

        $output->writeln("<info>Brand images resized with errors:</info>");
        foreach ($errors as $error) {
            $output->writeln("<error>{$error}</error>");
        }

        return Cli::RETURN_FAILURE;
    }
}

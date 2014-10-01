<?php

/*
 * This file is part of the Silpion/ComposerChecker package.
 *
 * (c) Julius Beckmann <beckmann@silpion.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silpion\ComposerChecker\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for removing src urls from a composer.lock file.
 *
 * @author Julius Beckmann <beckmann@silpion.de>
 */
class RemoveSrcCommand extends BaseCommand
{
    protected function configure()
    {
        $this
        ->setName('remove:src')
        ->setDescription('Removing src urls from a composer.lock file.')
        ->addArgument('file', InputArgument::REQUIRED, 'composer.lock file to check')
        ->addOption(
            'except',
            'e',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Patterns for src-urls that will not be removed.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        try {
            $json = $this->readJsonFromFile($input->getArgument('file'));
        }catch(\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return 1;
        }

        $newContent = $this->removeSrcUrls($json, $input->getOption('except'));

        $this->updateJsonContentInFile($input->getArgument('file'), $newContent);
    }

    private function removeSrcUrls($json, $exceptPatterns)
    {
        foreach ($json->packages as $key => $package) {

            if (isset($package->source)) {
                if (!$this->doesUrlMatchToAtLeastOnePattern($package->source->url, $exceptPatterns)) {
                    unset($package->source);
                }
            }else{
                $this->verbose(
                    'No Source found for package '.$package->name,
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            $json->packages[$key] = $package;
        }

        return $json;
    }
}
 
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
 * Command for removing dist urls from a composer.lock file.
 *
 * @author Julius Beckmann <beckmann@silpion.de>
 */
class RemoveDistCommand extends BaseCommand
{
    protected function configure()
    {
        $this
        ->setName('remove:dist')
        ->setDescription('Removing dist urls from a composer.lock file.')
        ->addArgument('file', InputArgument::REQUIRED, 'composer.lock file to check')
        ->addOption(
            'except',
            'e',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Patterns for dist-urls that will not be removed.'
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

        $newContent = $this->removeDistUrls($json, $input->getOption('except'));

        $this->updateJsonContentInFile($input->getArgument('file'), $newContent);
    }

    private function removeDistUrls($json, $exceptPatterns)
    {
        foreach($json->packages as $key => $package) {

            if(isset($package->dist)) {
                if(!$this->doesUrlMatchToAtLeastOnePattern($package->dist->url, $exceptPatterns)) {
                    unset($package->dist);
                }
            }else{
                $this->verbose(
                    'No Dist found for package '.$package->name,
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            $json->packages[$key] = $package;
        }

        return $json;
    }
}
 
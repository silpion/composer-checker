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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for checking the dist urls.
 *
 * @author Julius Beckmann <beckmann@silpion.de>
 */
class CheckDistCommand extends BaseCommand
{
    protected function configure()
    {
        $this
        ->setName('check:dist')
        ->setDescription('Matching the dist urls in a composer.lock file against some patterns.')
        ->addArgument('file', InputArgument::REQUIRED, 'composer.lock file to check')
        ->addOption(
            'url-pattern',
            'p',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Regex-Patterns for dist-urls.'
        )
        ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Will allow empty dist urls.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $patterns = $input->getOption('url-pattern');
        if (!$patterns) {
            $output->writeln('<error>Need at least one url-pattern.</error>');

            return 1;
        }

        if($input->getOption('allow-empty')) {
            $patterns[] = '^$';
        }

        try {
            $json = $this->readJsonFromFile($input->getArgument('file'));
        }catch(\Exception $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');
            return 1;
        }

        $errors = $this->searchUrlPatterns($json, $patterns, $output);
        if ($errors) {
            $rows = array();
            foreach ($errors as $package => $url) {
                $rows[] = array($package, $url);
            }

            $this->printTable($rows);

            return 1;
        }

        $output->writeln('<info>All urls valid.</info>');
    }

    /**
     * Will return a array of invalid packages and their urls determined by the given patterns.
     * A url is invalid if NONE of the given patterns has matched.
     *
     * @param \stdClass $json
     * @param array $patterns
     * @return array
     */
    protected function searchUrlPatterns(\stdClass $json, array $patterns)
    {
        $errors = array();
        foreach ($json->packages as $package) {
            if (!isset($package->dist)) {
                $this->verbose('Dist not found in "' . $package->name . "\"\n", OutputInterface::VERBOSITY_VERBOSE);
                $url = '';
            }else{
                $url = $package->dist->url;
            }

            if (!$this->doesUrlMatchToAtLeastOnePattern($url, $patterns)) {
                $errors[$package->name] = $url;
            }
        }

        return $errors;
    }
}
 
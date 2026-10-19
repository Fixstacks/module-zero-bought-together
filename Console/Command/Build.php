<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zero\BoughtTogether\Model\Config;
use Zero\BoughtTogether\Model\Pairs;

/**
 * bin/magento zero:bought-together:build [--days=365] [--min-orders=2]
 */
class Build extends Command
{
    public function __construct(private readonly Pairs $pairs, private readonly Config $config)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('zero:bought-together:build')
            ->setDescription('Rebuilds the "often bought together" pairs from the orders')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Orders of the last N days (default: as configured)')
            ->addOption(
                'min-orders',
                null,
                InputOption::VALUE_REQUIRED,
                'Orders holding both products for a pair to count (default: as configured)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) ($input->getOption('days') ?: $this->config->days());
        $min = (int) ($input->getOption('min-orders') ?: $this->config->minOrders());
        $rows = $this->pairs->build($days, $min);
        $output->writeln(
            sprintf('<info>%d pairs from the orders of the last %d days (%d+ orders each).</info>', $rows, $days, $min)
        );

        return Command::SUCCESS;
    }
}

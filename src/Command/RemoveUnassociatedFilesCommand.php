<?php

namespace Pixel\Module\ProductFiles\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUnassociatedFilesCommand extends Command
{
    protected function configure()
    {
        $this->setName("pixel:remove_unassociated_files");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Search for unassociated files...");
        $unassociatedFileIds = array_column(\Db::getInstance()->executeS("
            SELECT id 
            FROM " . _DB_PREFIX_ . "product_file 
            WHERE id NOT IN (SELECT pf.id FROM " . _DB_PREFIX_ . "product_file AS pf INNER JOIN " . _DB_PREFIX_ . "product AS p ON pf.id_product = p.id_product)
        "), "id");

        if (count($unassociatedFileIds) > 0) {
            $output->writeln("Removing " . count($unassociatedFileIds) . " unassociated files.");
            \Db::getInstance()->execute("DELETE FROM " . _DB_PREFIX_ . "product_file WHERE id IN (" . implode(",", $unassociatedFileIds) . ")");
        } else {
            $output->writeln("No unassociated files found.");
        }

        $output->writeln("Done.");
    }
}

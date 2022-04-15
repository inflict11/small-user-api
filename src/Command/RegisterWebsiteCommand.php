<?php

namespace App\Command;

use App\Entity\Website;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:register-website',
    description: 'Register new website',
)]
class RegisterWebsiteCommand extends Command
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the website.');
        $this->addArgument('url', InputArgument::REQUIRED, 'Url of the website.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        if ($name > 255) {
            $output->writeln("name should be <= 255 symbols");

            return Command::INVALID;
        }
        // TODO validate url? (should begin with http/https)
        $url = $input->getArgument('url');
        if ($url > 255) {
            $output->writeln("url should be <= 255 symbols");

            return Command::INVALID;
        }

        $em = $this->getDoctrine()->getManager();

        $website = new Website();
        $website->setName($name);
        $website->setUrl($url);

        $key = bin2hex(random_bytes(64));
        // TODO Check uniqueness before saving
        $website->setApiKey($key);

        $em->persist($website);
        $em->flush();

        $output->writeln("Your key: $key");

        return Command::SUCCESS;
    }
}

<?php declare(strict_types=1);

namespace Sven\DasGoogle\Command;

use Sven\DasGoogle\Service\GooglePlacesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sdg:test', description: 'Testet die Google Places API Verbindung')]
class TestConnectionCommand extends Command
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Teste Verbindung...');

        $testResult = $this->googlePlacesService->testConnection();
        $output->writeln('Test: ' . ($testResult['success'] ? 'OK' : 'FEHLER') . ' - ' . $testResult['message']);

        $output->writeln('');
        $output->writeln('Lade Place-Daten...');

        $data = $this->googlePlacesService->getPlaceData();

        if ($data === null) {
            $output->writeln('<error>getPlaceData() gibt NULL zurueck!</error>');
        } else {
            $output->writeln('<info>Name: ' . $data['name'] . '</info>');
            $output->writeln('<info>Rating: ' . $data['rating'] . '/5</info>');
            $output->writeln('<info>Bewertungen: ' . $data['userRatingsTotal'] . '</info>');
            $output->writeln('<info>Reviews: ' . count($data['reviews']) . '</info>');
        }

        return Command::SUCCESS;
    }
}

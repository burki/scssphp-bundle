<?php declare(strict_types=1);
namespace Armin\ScssphpBundle\Command;

use Armin\ScssphpBundle\Scss\Parser;
use Armin\ScssphpBundle\Scss\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompileCommand extends Command
{
    /**
     * @var string
     */
    private $choice;

    private $scssParser;

    private $interacted = false;

    public function __construct(?string $name = null, ?Parser $scssParser = null)
    {
        parent::__construct($name);
        $this->scssParser = $scssParser;
    }

    protected function configure(): void
    {
        $this
            ->setName('scssphp:compile')
            ->setDescription('Compiles configured SCSS sources.')
            ->addArgument(
                'asset',
                InputArgument::OPTIONAL,
                'Name or number of asset to compile. Use "all" to re-compile all configured assets.'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $config = $this->scssParser->getConfiguration();
        if (!isset($config['assets']) || count($config['assets']) === 0) {
            throw new \RuntimeException('No SCSS assets configured!');
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $config = $this->scssParser->getConfiguration();

        $this->choice = $input->getArgument('asset') ?? 'all';

        $choices = ['all'];
        foreach (array_keys($config['assets']) as $assetName) {
            $choices[] = $assetName;
        }

        // On invalid input
        $forceAsking = false;
        if (is_numeric($this->choice)) {
            $this->choice = (int) $this->choice;
            if (!isset($choices[$this->choice])) {
                $forceAsking = true;
                $this->choice = 'all';
            } else {
                $this->choice = $choices[$this->choice];
            }
        } elseif (!in_array($this->choice, $choices, true)) {
            $forceAsking = true;
            $this->choice = 'all';
        }

        if ($forceAsking || (!$input->getArgument('asset') && $input->getArgument('asset') !== '0')) {
            $this->choice = $io->choice(
                'There are several assets configured. Which one do you want to compile?',
                $choices,
                $this->choice
            );
            $this->interacted = true;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->getFormatter()->setStyle('success', new OutputFormatterStyle('green', null, ['bold']));
        $io->getFormatter()->setStyle('notice', new OutputFormatterStyle('blue'));
        $config = $this->scssParser->getConfiguration();

        if (!$input->isInteractive()) {
            if (is_numeric($input->getArgument('asset')) && $input->getArgument('asset') !== '0') {
                $assetList = '-> ' . implode(PHP_EOL . '-> ', array_keys($config['assets']));
                throw new \RuntimeException(
                    'In non-interactive mode you need to use the asset name instead of its number in list.' . PHP_EOL .
                    'The following assets are available:' . PHP_EOL . $assetList
                );
            }
            if ($input->getArgument('asset') === '0') {
                $this->choice = 'all';
            } else {
                $this->choice = $input->getArgument('asset');
            }
        }

        $error = false;
        if ($this->choice !== 'all') {
            $confirm = !$this->interacted
                ? $io->confirm('Do you want to compile "<comment>' . $this->choice . '</comment>"?')
                : true;
            if (!$confirm) {
                $io->writeln('Aborted.');
                return Command::INVALID;
            }
            $result = $this->parse($this->choice, $io);
            $error = !$result || !$result->isSuccessful();
        } else {
            $confirm = !$this->interacted
                ? $io->confirm('Do you want to compile <comment>' . count($config['assets']) . ' assets</comment>?')
                : true;
            if (!$confirm) {
                $io->writeln('Aborted.');
                return Command::INVALID;
            }
            foreach (array_keys($config['assets']) as $assetName) {
                $result = $this->parse($assetName, $io);
                if (!$result || !$result->isSuccessful()) {
                    $error = true;
                }
            }
        }
        $style = $error ? 'error' : 'success';
        $io->writeln('<'. $style . '>Finished SCSS compiling ' .
                    ($error ? 'with errors!' : 'successfully.') . '</' . $style . '>');

        return (int) $error;
    }

    protected function parse(string $assetName, SymfonyStyle $io): ?Result
    {
        $add = file_exists($this->scssParser->makeJob($assetName)->getDestinationPath()) ? ' (and overwriting!)' : '';
        $io->write('Compiling' . $add . ' "<comment>' . $assetName . '</comment>"... ');
        $this->scssParser->parse($assetName, true);
        if ($result = $this->scssParser->getResult($assetName)) {
            $io->write($result->isSuccessful() ? '<success>OK</success>' : '<error>ERROR</error>');
            $io->writeln(' (' . round($result->getDuration(), 3) . 's)');
            if (!$result->isSuccessful()) {
                $io->error('Error during compiling "' . $assetName . '"' . PHP_EOL . $result->getErrorMessage());
            } else {
                $size = round($result->getCompiledSize() / 1024, 1);
                $io->writeln('<notice>Written ' . $size . ' KB to ' .
                              $result->getJob()->getDestinationPath() . '</notice>');
            }
        }
        return $result;
    }
}

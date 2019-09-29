<?php
declare(strict_types=1);

namespace AmsterdamPHP\TrelloChecklister\Command;

use AmsterdamPHP\TrelloChecklister\ExecutionResult;
use AmsterdamPHP\TrelloChecklister\Trello\BoardManager;
use AmsterdamPHP\TrelloChecklister\Trello\BoardTemplate;
use Maknz\Slack\AttachmentField;
use Maknz\Slack\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function sprintf;
use const DATE_RFC3339;

final class CreateBoards extends Command
{
    protected static $defaultName = 'trello:boards:create';

    /**
     * @var BoardManager
     */
    private $boardManager;

    /**
     * @var Client
     */
    private $slack;

    public function __construct(BoardManager $boardManager, Client $slack)
    {
        $this->boardManager = $boardManager;
        $this->slack        = $slack;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates new boards for future events, with checklist ready.')
            ->addArgument(
                'template',
                InputArgument::OPTIONAL,
                'Board template to use',
                BoardTemplate::DEFAULT_TEMPLATE
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $template = BoardTemplate::fromYamlFile($input->getArgument('template'));
        $result   = $this->boardManager->createNextBoards($template);

        $this->announceNewBoardOnSlack($result);

        if ($result->isSuccess()) {
            $output->writeln(sprintf('<info>%s</info>', $result->getMessage()));
            $output->writeln('');
            $output->writeln(
                sprintf('<comment>Board Url:</comment> %s', $result->getContext()['board_url'] ?? '-n/a-')
            );
            $output->writeln(
                sprintf('<comment>From Template:</comment> %s', $result->getContext()['template'] ?? '-n/a-')
            );
            $output->writeln(
                sprintf(
                    '<comment>For Meetup on:</comment> %s',
                    $result->getContext()['date']->format(DATE_RFC3339) ?? '-n/a-'
                )
            );
        } else {
            $output->writeln(sprintf('<error>%s</error>', $result->getMessage()));
        }
    }

    private function announceNewBoardOnSlack(ExecutionResult $result): void
    {
        $this->slack->attach(
            [
                'color' => $result->isSuccess() ? 'good':'danger',
                'fields' => [
                    new AttachmentField(
                        [
                            'title' => 'Board',
                            'value' => $result->getContext()['board_url'] ?? 'n/a',
                            'short' => false,
                        ]
                    ),
                    new AttachmentField(
                        [
                            'title' => 'Template used',
                            'value' => $result->getContext()['template'] ?? 'n/a',
                            'short' => false,
                        ]
                    ),
                    new AttachmentField(
                        [
                            'title' => 'Meetup',
                            'value' => $result->getContext()['date']->format('d/F/Y') ?? 'n/a',
                            'short' => false,
                        ]
                    ),
                ],
            ]
        )->send($result->getMessage());
    }
}

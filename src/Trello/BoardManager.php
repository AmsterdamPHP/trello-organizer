<?php
declare(strict_types=1);

namespace AmsterdamPHP\TrelloChecklister\Trello;

use AmsterdamPHP\TrelloChecklister\ExecutionResult;
use DateTimeImmutable;
use Stevenmaguire\Services\Trello\Client;
use function array_filter;

final class BoardManager
{
    /**
     * @var Client
     */
    private $trello;

    private $organizationId;

    public function __construct(Client $trello, $organizationId)
    {
        $this->trello         = $trello;
        $this->organizationId = $organizationId;
    }

    public function createNextBoards(BoardTemplate $template): ExecutionResult
    {
        $nextMeetup = new DateTimeImmutable('third thursday of next month');
        $title      = $template->makeBoardName($nextMeetup);

        if ($this->boardExists($title)) {
            return new ExecutionResult(
                false,
                'Skipped, board already exists.',
                ['date' => $nextMeetup, 'template' => $template->getName()]
            );
        }

        $board = $template->createBoardFromTemplate($this->organizationId, $nextMeetup, $this->trello);

        return new ExecutionResult(
            true,
            sprintf(
                'Successfully created board.'
            ),
            [
                'board_url' => $board->url,
                'template'  => $template->getName(),
                'date'      => $nextMeetup,
            ]
        );
    }

    private function boardExists(string $title): bool
    {
        $monthly = array_filter(
            $this->trello->getOrganizationBoards($this->organizationId),
            static function (object $board) use ($title) {
                return $board->name === $title;
            }
        );

        return count($monthly) > 0;
    }
}

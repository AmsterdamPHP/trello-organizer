<?php
declare(strict_types=1);

namespace AmsterdamPHP\TrelloChecklister\Trello;

use DateTimeImmutable;
use Stevenmaguire\Services\Trello\Client;
use Symfony\Component\Yaml\Yaml;
use function array_shift;
use function basename;
use function is_object;
use function sprintf;

final class BoardTemplate
{
    public const DEFAULT_TEMPLATE = __DIR__ . '/../../config/checklists/monthly-meeting.yaml';

    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $name;

    public function __construct(array $config, string $name)
    {
        $this->config = $config;
        $this->name   = $name;
    }

    public static function fromYamlFile(string $filename): self
    {
        return new static(Yaml::parseFile($filename), basename($filename));
    }

    public function getNameTemplate(): string
    {
        return $this->config['name'];
    }

    public function createBoardFromTemplate(string $organizationId, DateTimeImmutable $when, Client $client): object
    {
        $boardData = [
            'name'           => $this->makeBoardName($when),
            'desc'           => $this->config['desc'] ?? null,
            'defaultLists'   => $this->config['lists'] === 'default',
            'idOrganization' => $organizationId,
        ];

        $board = $client->addBoard($boardData);
        $this->createCards($client, $when, $this->getToDoListId($board->id, $client));

        return $board;
    }

    public function createCards(Client $client, DateTimeImmutable $referenceDate, string $firstColumnId): void
    {
        foreach ($this->config['cards'] as $cardData) {
            $card = [
                'name'   => $cardData['name'],
                'desc'   => $cardData['desc'],
                'due'    => $referenceDate->modify($cardData['due'])->format('c'),
                'idList' => $firstColumnId,
            ];

            $client->addCard($card);
        }
    }

    public function makeBoardName(DateTimeImmutable $when): string
    {
        return sprintf($this->getNameTemplate(), $when->format('F/Y'));
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function getToDoListId(string $boardId, Client $client): ?string
    {
        $lists        = $client->getBoardLists($boardId);
        $startingList = array_shift($lists);

        if (is_object($startingList) === false) {
            return null;
        }

        return $startingList->id;
    }
}

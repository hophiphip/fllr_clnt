<?php
namespace App\Command;

use App\Models\Colors;
use App\Models\Field;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// TODO: Add time stat
// TODO: Use a better solution method

class PlayFillerCommand extends Command {
    /**
     * @var string $defaultName default CLI command name
     */
    protected static $defaultName = 'play';

    /**
     * @var string $defaultDescription CLI command description
     */
    protected static $defaultDescription = 'I wanna play filler';

    private HttpClientInterface $client;

    /**
     * @var string $apiGameRoute route for the game API
     */
    private string $apiGameRoute = '/api/game';

    /**
     * @var string $gameServerUrl contains filler game server URL
     */
    private string $gameServerUrl;

    /**
     * @var string $gameId contains filler game id
     */
    private string $gameId;

    /**
     * @var string $gamePlayerId contains filler game player id
     */
    private string $gamePlayerId;

    /**
     * @var bool $autoSubmitColor does client need to auto submit color (by default set to `true`)
     */
    private bool $autoSubmitColor;

    /**
     * @var bool $enableStatistics if `true` prints total time for game-solver algorithm
     */
    private bool $enableStatistics;

    /**
     * @param HttpClientInterface $client
     * @param string $gameServerUrl
     * @param string $gameId
     * @param string $gamePlayerId
     * @param bool $autoSubmitColor
     */
    public function __construct(
        HttpClientInterface $client,
        string $gameServerUrl = "",
        string $gameId = "",
        string $gamePlayerId = "0",
        bool $autoSubmitColor = true)
    {
        $this->client = $client;
        $this->gameServerUrl = $gameServerUrl;
        $this->gameId = $gameId;
        $this->gamePlayerId = $gamePlayerId;
        $this->autoSubmitColor = $autoSubmitColor;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Give it an API and it will do the rest...')
            ->addOption(
                'gameServer',
                's',
                InputOption::VALUE_REQUIRED,
                'Game server URL'
            )

            ->addOption(
                'gameId',
                'g',
                InputOption::VALUE_OPTIONAL,
                'Game id'
            )

            ->addOption(
                'playerId',
                'p',
                InputOption::VALUE_OPTIONAL,
                'In game player id'
            )

            ->addOption(
                'newGame',
                'a',
                InputOption::VALUE_NONE,
                'Request a new game id'
            )

            ->addOption(
            'noSubmit',
            'x',
            InputOption::VALUE_NONE,
            'Make a PUT request to the API with calculated move'
    );
    }

    // TODO: Needs a proper refactoring .. but i don't care for now

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Set parameters
        if ($input->getOption('gameServer') == null) {
            $output->writeln([
                'Server url is required'
            ]);

            return  -1;
        }
        $this->gameServerUrl = $input->getOption('gameServer');

        // Add HTTP prefix if not provided
        if (!str_starts_with($this->gameServerUrl, 'http')) {
            $this->gameServerUrl = 'http://' . $this->gameServerUrl;
        }

        $this->gameId = $input->getOption('gameId') == null ? "" : $input->getOption('gameId');
        $this->gamePlayerId = $input->getOption('playerId') == null ? "" : $input->getOption('playerId');

        // Don't submit next move if `noSubmit` flag was provided
        if ($input->getOption('noSubmit')) {
            $this->autoSubmitColor = false;
        }

        // MODE 1: Generate game id
        // If `new game` flag was provided then request a new game ID
        $isNewGame = $input->getOption('newGame');
        if ($isNewGame) {
            $newGame = $this->requestNewGameId($this->gameServerUrl . $this->apiGameRoute);

            if ($newGame['error'] != '') {
                $output->writeln([
                    'App finished with error: ' . $newGame['error']
                ]);

                return  -1;
            } else {
                $io = new SymfonyStyle($input, $output);
                $io->success('New game id was generated: ' . $newGame['newGameId']);
            }

            return 0;
        }

        // MODE 2: Calculate a player next move
        // Validate arguments
        if ($this->gameId == '') {
            $output->writeln([
               'Game ID was not provided',
            ]);

            return -1;
        }

        // Validate parameters
        if (!($this->gamePlayerId == '1') && !($this->gamePlayerId == '2')) {
            $output->writeln([
                'Player Id must be an integer from {1, 2}',
                '..you got: ' . $this->gamePlayerId,
            ]);

            return -1;
        }

        // Welcome message
        $output->writeln([
            'Ready! Get set! Go!',
            '-------------------',
        ]);

        // Log URL & PID
        $output->writeln([
           'URL: ' . $this->gameServerUrl . $this->apiGameRoute . $this->gameId,
           'PID: ' . $this->gamePlayerId,
            '-------------------',
        ]);

        // Make a game move
        $result = $this->makeInGameMove($input, $output, $this->gameServerUrl . $this->apiGameRoute, $this->gameId, $this->gamePlayerId);
        if ($result['error'] != '') {
            $output->writeln([
                'App finished with error: ' . $result['error']
            ]);

            return -1;
        }

        // Debug log status
        $output->writeln([
            'winnerId: ' . $result['winnerId'] . ", previously submitted color is: " . $result['submittedColor']
        ]);

        return $result['winnerId'];
    }


    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    #[ArrayShape(['error' => "string", 'winnerId' => "int", 'submittedColor' => 'string'])]
    public function makeInGameMove(InputInterface $input, OutputInterface $output, string $apiRoute, string $gameId, string $playerId): array
    {
        $result = [
            'error' => '',
            'winnerId' => 0,
            'submittedColor' => '',
        ];

        // Get game status
        $response = $this->client->request('GET', $apiRoute . '/' . $gameId, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        // Verify GET request status code
        $status = $response->getStatusCode();
        if ($status != 200) {
            switch ($status) {
                case 400: {
                    $result['error'] = "Incorrect request parameters";
                } break;

                case 404: {
                    $result['error'] = "Incorrect game id";
                } break;

                default: {
                    $result['error'] = "Unknown GET error. HTTP code: " . $status;
                }
            }

            return $result;
        }

        // Parse game model
        $content = $response->toArray();

        // If the game has a winner no need to do anything
        if ($content['winnerPlayerId'] != 0) {
            $result['winnerId'] = $content['winnerPlayerId'];
            return $result;
        }

        // If is it not provided player move - set error and return
        if ($content['currentPlayerId'] != $playerId) {
            $result['error'] = 'Not a player ' . $playerId . ' turn yet';
            return $result;
        }

        // Game solver
        //
        { // START_OF_SCOPE
            // Player 1 & 2 stats
            $stats = [
                1 => [],
                2 => [],
            ];

            foreach ($content['field']['cells'] as $i => $cell) {
                if ($cell['playerId'] != 0) {
                    array_push($stats[$cell['playerId']], $i);
                }
            }

            $colorStats = [
                0 => [],
                1 => [],
                2 => [],
                3 => [],
                4 => [],
                5 => [],
                6 => [],
            ];

            unset($colorStats[Colors::$colorsTable[$content['players'][1]['color']]]);
            unset($colorStats[Colors::$colorsTable[$content['players'][2]['color']]]);

            // TODO: This approach is the best on server as only the server knows th chronology on players' cells-statistics
            foreach ($colorStats as $colorKey => $colorStat) {
                $field = Field::fromArray($content['field']);

                $currentPlayerId = $content['currentPlayerId'];
                $playerColor = Colors::$colors[$colorKey];
                $playerStats = $stats[$currentPlayerId];
            
                for ($i = 0; $i < count($playerStats); $i++) {
                    $cellIndex = $playerStats[$i];
            
                    // left
                    if (!($field->hasNoLeftCell($cellIndex))) {
                        $leftIndex = $cellIndex - $field->width;
                        if ($field->isAssignable($cellIndex, $leftIndex, $playerColor)) {
                            // assign other cell to current player id
                            $field->cells[$leftIndex]["playerId"] =
                                $field->cells[$cellIndex]["playerId"];
                            // add other cell to current player cells
                            array_push($playerStats, $leftIndex);
                        }
                    }
            
                    // top
                    if (!($field->hasNoTopCell($cellIndex))) {
                        $topIndex = $cellIndex - $field->width + 1;
                        if ($field->isAssignable($cellIndex, $topIndex, $playerColor)) {
                            // assign other cell to current player id
                            $field->cells[$topIndex]["playerId"] =
                                $field->cells[$cellIndex]["playerId"];
                            // add other cell to current player cells
                            array_push($playerStats, $topIndex);
                        }
                    }
            
                    // right
                    if (!($field->hasNoRightCell($cellIndex))) {
                        $rightIndex = $cellIndex + $field->width;
                        if ($field->isAssignable($cellIndex, $rightIndex, $playerColor)) {
                            // assign other cell to current player id
                            $field->cells[$rightIndex]["playerId"] =
                                $field->cells[$cellIndex]["playerId"];
                            // add other cell to current player cells
                            array_push($playerStats, $rightIndex);
                        }
                    }
            
                    // bottom
                    if (!($field->hasNoBottomCell($cellIndex))) {
                        $bottomIndex = $cellIndex + $field->width - 1;
                        if ($field->isAssignable($cellIndex, $bottomIndex, $playerColor)) {
                            // assign other cell to current player id
                            $field->cells[$bottomIndex]["playerId"] =
                                $field->cells[$cellIndex]["playerId"];
                            // add other cell to current player cells
                            array_push($playerStats, $bottomIndex);
                        }
                    }
                }
                $colorStats[$colorKey] = $playerStats;
            }


        } // END_OF_SCOPE

        // Get next color number
        $nextColorKey = array_key_first($colorStats);
        $nextColorKeyStat = count($colorStats[$nextColorKey]);
        foreach ($colorStats as $colorKey => $colorStat) {
            if (count($colorStats[$nextColorKey]) < count($colorStat)) {
                $nextColorKey = $colorKey;
                $nextColorKeyStat = count($colorStat);
            }
        }

        $result['submittedColor'] = Colors::$colors[$nextColorKey];

        // Handle `Hex` colors and `string` colors
        $nextColor = "";
        if (array_key_exists($content['field']['cells'][1]['color'], Colors::$toFancyColors)) {
            $nextColor = Colors::$toFancyColors[$nextColorKey];
        } else if(array_key_exists($content['field']['cells'][1]['color'], Colors::$toNormalColors)) {
            $nextColor = Colors::$toNormalColors[$nextColorKey];
        }

        // TODO: PUT this request to a separate function
        // Submit the color for the player if necessary
        if ($this->autoSubmitColor) {
            $putResponse = $this->client->request('PUT', $apiRoute . '/' . $gameId, [
                'body' => [
                    'playerId' => $content['currentPlayerId'],
                    'color' => $nextColor,
                ],

                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $putContent = $putResponse->toArray();
            $result['winnerId'] = $putContent['winnerPlayerId'];

            $putStatus = $putResponse->getStatusCode();
            if ($putStatus != 201) {
                switch ($putStatus) {
                    case 400: {
                        $result['error'] = 'Incorrect request parameters';
                    } break;

                    case 403: {
                        $result['error'] = "Provided player can't move right now";
                    } break;

                    case 409: {
                        $result['error'] = "Provided player can't choose this color";
                    } break;

                    case 404: {
                        $result['error'] = "Incorrect game id";
                    } break;

                    default: {
                        $result['error'] = "Unknown PUT error. HTTP error code: " . $putStatus;
                    }
                }

                return $result;
            }
        } else {
            $playerOneStats = count($stats[1]);
            $playerTwoStats = count($stats[2]);
            $content['currentPlayerId'] == 1 ? $playerOneStats += $nextColorKeyStat : $playerTwoStats += $nextColorKeyStat;

            $cellCount = count($content['field']['cells']);

            if (($playerOneStats / (float) $cellCount) > 0.5) $result['winnerId'] = 1;
            else if (($playerTwoStats / (float) $cellCount) > 0.5) $result['winnerId'] = 2;
        }

        return $result;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[ArrayShape(['error' => "string", 'newGameId' => "string"])]
    public function requestNewGameId(string $apiUrl, int $width = 25, int $height = 15): array {
        $result = [
            'error' => '',
            'newGameId' => '',
        ];

        $response = $this->client->request('POST', $apiUrl, [
            'body' => [
                'width' => $width,
                'height' => $height,
            ],

            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status != 201) {
            switch ($status) {
                case 400: {
                    $result['error'] = 'Incorrect field size.';
                } break;

                case 500: {
                    $result['error'] = 'Internal server error';
                } break;

                default: {
                    $result['error'] = 'Unknown POST error, HTTP code: ' . $status;
                }
            }
        }

        $content = [];
        try {
            $content = $response->toArray();
        } catch (ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface | DecodingExceptionInterface $e) {
        } catch (TransportExceptionInterface $e) {
            $result['error'] = 'It all failed miserably..';
        } finally {
            $result['newGameId'] = $content['id'];
        }

        return $result;
    }
}
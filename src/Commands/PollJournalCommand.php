<?php

declare(strict_types=1);

namespace Beziworld\BaseLinker\Commands;

use Beziworld\BaseLinker\BaseLinkerManager;
use Beziworld\BaseLinker\Exceptions\BaseLinkerException;
use Beziworld\BaseLinker\Journal\JournalPoller;
use Beziworld\BaseLinker\Support\Cast;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Polls the order (and optionally the order return) journal of one or every configured connection.
 */
final class PollJournalCommand extends Command
{
    /** @var string */
    protected $signature = 'baselinker:poll
        {connection? : Connection to poll; every configured connection when omitted}
        {--orders : Poll the order journal (getJournalList)}
        {--returns : Poll the order return journal (getOrderReturnJournalList)}';

    /** @var string */
    protected $description = 'Poll the BaseLinker journals and dispatch an event for every new entry';

    public function handle(BaseLinkerManager $manager, JournalPoller $poller, Config $config): int
    {
        $journals = $this->journals($config);
        $argument = $this->argument('connection');
        $connections = is_string($argument) && $argument !== '' ? [$argument] : $manager->connectionNames();
        $failed = false;

        foreach ($connections as $name) {
            try {
                $client = $manager->connection($name);

                if (in_array(JournalPoller::ORDERS, $journals, true)) {
                    $count = $poller->pollOrders($client);
                    $this->line(sprintf('[%s] order journal: %d new event(s).', $name, $count));

                    if ($count === 0 && $poller->cursor($name) === 0) {
                        $this->warn(sprintf('[%s] The order journal returned no entries. Make sure getJournalList is enabled in the API settings of the BaseLinker account.', $name));
                    }
                }

                if (in_array(JournalPoller::RETURNS, $journals, true)) {
                    $count = $poller->pollReturns($client);
                    $this->line(sprintf('[%s] return journal: %d new event(s).', $name, $count));
                }
            } catch (BaseLinkerException $exception) {
                $failed = true;
                $this->error(sprintf('[%s] %s', $name, $exception->getMessage()));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function journals(Config $config): array
    {
        $journals = [];

        if ($this->option('orders') === true) {
            $journals[] = JournalPoller::ORDERS;
        }

        if ($this->option('returns') === true) {
            $journals[] = JournalPoller::RETURNS;
        }

        return $journals !== [] ? $journals : Cast::strings($config->get('baselinker.journal.poll', [JournalPoller::ORDERS]));
    }
}

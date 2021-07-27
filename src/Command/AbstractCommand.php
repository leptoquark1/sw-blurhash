<?php declare(strict_types=1);

namespace Eyecook\Blurhash\Command;

use Eyecook\Blurhash\Configuration\ConfigService;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The AbstractCommand Class builds the basis of all descendant commands and provides shared functionality and may
 * enhance maintainability, clarity and usability.
 *
 * @package Eyecook\Blurhash
 * @author David Fecke (leptoquark1)
 */
abstract class AbstractCommand extends Command
{
    protected Container $container;
    protected ConfigService $config;
    protected InputInterface $input;
    protected OutputInterface $output;
    protected SymfonyStyle $ioHelper;
    protected Context $context;

    public function __construct(string $name)
    {
        parent::__construct('ec:blurhash:' . $name);
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function setConfigService(ConfigService $configService): void
    {
        $this->config = $configService;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->createContext();
        $this->input = $input;
        $this->output = $output;
        $this->ioHelper = new SymfonyStyle($input, $output);
        $this->initializeCommand();
    }

    protected function createContext(): void
    {
        $injectedContext = $this->container->get(Context::class,ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $this->context = new Context($injectedContext ?? new SystemSource());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->handle();
        if ($result === null || is_int($result) === false) {
            return 0;
        }

        return $result;
    }

    protected function initializeCommand(): void {}
    abstract public function handle(): ?int;
}

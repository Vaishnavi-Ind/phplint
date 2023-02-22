<?php

declare(strict_types=1);

/*
 * This file is part of the overtrue/phplint package
 *
 * (c) overtrue
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\PHPLint\Tests\Configuration;

use Overtrue\PHPLint\Command\LintCommand;
use Overtrue\PHPLint\Configuration\ConsoleOptionsResolver;
use Overtrue\PHPLint\Configuration\OptionDefinition;
use Overtrue\PHPLint\Configuration\Resolver;
use Overtrue\PHPLint\Event\EventDispatcher;
use Overtrue\PHPLint\Tests\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

use function dirname;
use function is_string;
use function realpath;

final class ConsoleConfigTest extends TestCase
{
    /**
     * @covers \Overtrue\PHPLint\Configuration\ConsoleOptionsResolver::getOption
     */
    public function testConfigFileNotReadable(): void
    {
        $dispatcher = new EventDispatcher([]);
        $definition = (new LintCommand($dispatcher))->getDefinition();

        $input = new ArrayInput(['--configuration' => 'does-not-exists.yaml'], $definition);

        $resolver = new ConsoleOptionsResolver($input);

        $this->assertFalse(realpath($resolver->getOption(OptionDefinition::CONFIGURATION)));
    }

    /**
     * @covers \Overtrue\PHPLint\Configuration\ConsoleOptionsResolver::getOptions
     * @dataProvider commandInputProvider
     */
    public function testCommandConfig(array $arguments, callable $fetchExpected): void
    {
        $dispatcher = new EventDispatcher([]);
        $definition = (new LintCommand($dispatcher))->getDefinition();

        $input = new ArrayInput($arguments, $definition);

        $resolver = new ConsoleOptionsResolver($input);

        $this->assertSame($fetchExpected($resolver, $arguments), $resolver->getOptions());
    }

    public static function commandInputProvider(): array
    {
        return [
            'only default values' => [[], __CLASS__ . '::expectedOnlyDefaults'],
            'only path modified' => [['path' => dirname(__DIR__)], __CLASS__ . '::expectedPathModified'],
            'multiple path modified' => [['path' => [dirname(__DIR__) . '/Cache', __DIR__]], __CLASS__ . '::expectedPathModified'],
            'without external configuration' => [['--no-configuration' => true], __CLASS__ . '::expectedExternalConfigNotFetched'],
            'with external empty configuration' => [['--configuration' => 'tests/Configuration/empty.yaml'], __CLASS__ . '::expectedExternalEmptyConfig'],
            'output to JSON format on Stdout' => [['--log-json' => null], __CLASS__ . '::expectedJsonOutputFormat'],
            'output to JSON format on File' => [['--log-json' => '/tmp/phplint.json'], __CLASS__ . '::expectedJsonOutputFormat'],
            'output to XML format on Stdout' => [['--log-junit' => true], __CLASS__ . '::expectedXmlOutputFormat'],
            'output to XML format on File' => [['--log-junit' => '/tmp/phplint.xml'], __CLASS__ . '::expectedXmlOutputFormat'],
        ];
    }

    protected static function expectedOnlyDefaults(Resolver $resolver): array
    {
        return self::getExpectedValues($resolver);
    }

    protected static function expectedPathModified(Resolver $resolver, array $arguments): array
    {
        $expected = self::getExpectedValues($resolver);
        $expected['path'] = (array) $arguments['path'];
        return $expected;
    }

    protected static function expectedExternalConfigNotFetched(Resolver $resolver): array
    {
        return self::getExpectedValues($resolver);  // expected only default arguments/options from command line
    }

    protected static function expectedExternalEmptyConfig(Resolver $resolver, array $arguments): array
    {
        return self::getExpectedValues($resolver);  // expected only default arguments/options from command line
    }

    protected static function expectedJsonOutputFormat(Resolver $resolver, array $arguments): array
    {
        $expected = self::getExpectedValues($resolver);
        $logJson = $arguments['--log-json'];
        $expected['log-json'] = (is_string($logJson)) ? $logJson : (empty($logJson) ? null : 'php://stdout');
        return $expected;
    }

    protected static function expectedXmlOutputFormat(Resolver $resolver, array $arguments): array
    {
        $expected = self::getExpectedValues($resolver);
        $logJunit = $arguments['--log-junit'];
        $expected['log-junit'] = (is_string($logJunit)) ? $logJunit : (empty($logJunit) ? null : 'php://stdout');
        return $expected;
    }

    protected static function getExpectedValues(Resolver $resolver): array
    {
        $factory = $resolver->factory();
        return $factory->resolve();
    }
}

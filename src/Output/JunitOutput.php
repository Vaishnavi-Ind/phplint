<?php

declare(strict_types=1);

namespace Overtrue\PHPLint\Output;

use DateTime;
use DOMDocument;
use DOMElement;
use Symfony\Component\Console\Output\StreamOutput;

use function count;
use function fclose;

/**
 * @author Laurent Laville
 * @since Release 9.0.0
 */
final class JunitOutput extends StreamOutput implements OutputInterface
{
    public function format(LinterOutput $results): void
    {
        $failures = $results->getFailures();
        $context = $results->getContext();

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $rootElement = $document->createElement('testsuites');
        $document->appendChild($rootElement);

        $suite = new DOMElement('testsuite');
        $rootElement->appendChild($suite);
        $suite->setAttribute('name', 'PHP Linter');
        $suite->setAttribute('timestamp', (new DateTime())->format(DateTime::ISO8601));
        $suite->setAttribute('time', $context['time_usage']);
        $suite->setAttribute('tests', '1');
        $suite->setAttribute('errors', (string) count($failures));

        $testCase = new DOMElement('testcase');
        $suite->appendChild($testCase);
        $testCase->setAttribute('errors', (string) count($failures));
        $testCase->setAttribute('failures', '0');

        foreach ($failures as $errorName => $value) {
            $error = $testCase->ownerDocument->createElement('error', $errorName);
            $testCase->appendChild($error);
            $error->setAttribute('type', 'Error');
            $error->setAttribute('message', $value['error']);
        }

        $this->write($document->saveXML());
        fclose($this->getStream());
    }
}

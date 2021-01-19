<?php declare(strict_types=1);

namespace App\Tests\Controller\Jury;

use App\Tests\BaseTest;

class ClarificationControllerTest extends BaseTest
{
    protected static $roles = ['jury'];

    /**
     * Test that the jury clarifications page contains the correct information
     */
    public function testClarificationRequestIndex() : void
    {
        $this->verifyPageResponse('GET', '/jury', 200);
        $link = $this->verifyLinkToURL('Clarifications',
                                       'http://localhost/jury/clarifications');
        $crawler = $this->client->click($link);

        $h3s = $crawler->filter('h3')->extract(array('_text'));
        self::assertEquals('New requests', $h3s[0]);
        self::assertEquals('Handled requests', $h3s[1]);
        self::assertEquals('General clarifications', $h3s[2]);

        self::assertSelectorExists('html:contains("Can you tell me how")');
        self::assertSelectorExists('html:contains("21:47")');
    }

    /**
     * Test that the jury can view a clarification
     */
    public function testClarificationRequestView() : void
    {
        $this->verifyPageResponse('GET', '/jury/clarifications/1', 200);

        $pres = $this->getCurrentCrawler()->filter('pre')->extract(array('_text'));
        self::assertEquals('Can you tell me how to solve this problem?',
                           $pres[0]);
        self::assertEquals("> Can you tell me how to solve this problem?\r\n\r\nNo, read the problem statement.",
                           $pres[1]);

        $this->verifyLinkToURL('Example teamname (t2)',
                               'http://localhost/jury/teams/2');
    }

    /**
     * Test that the jury can send a clarification to everyone
     */
    public function testClarificationRequestComposeForm() : void
    {
        $this->verifyPageResponse('GET', '/jury/clarifications', 200);
        $link = $this->verifyLinkToURL('Send clarification',
                                       'http://localhost/jury/clarifications/send');

        $crawler = $this->client->click($link);

        $h1s = $crawler->filter('h1')->extract(array('_text'));
        self::assertEquals('Send Clarification', $h1s[0]);

        $options = $crawler->filter('option')->extract(array('_text'));
        self::assertEquals('ALL', $options[1]);
        self::assertEquals('DOMjudge (t1)', $options[2]);
        self::assertEquals('Example teamname (t2)', $options[3]);

        $labels = $crawler->filter('label')->extract(array('_text'));
        self::assertEquals('Send to:', $labels[0]);
        self::assertEquals('Subject:', $labels[1]);
        self::assertEquals('Message:', $labels[2]);

        $this->client->submitForm('Send', [
            'sendto' => '',
            'problem' => '2-tech',
            'bodytext' => 'This is a clarification',
        ]);

        $this->client->followRedirect();

        self::assertSelectorTextContains('div.col-sm strong', 'All');
        self::assertSelectorTextContains('span.clarification-subject',
                                         'demo - Technical issue');
        self::assertSelectorTextContains('pre.output-text',
                                         'This is a clarification');
    }
}

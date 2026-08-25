<?php
declare(strict_types = 1);

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 *
 * @covers \CRM_Mailingtools_RegexToken
 */
class CRM_Mailingtools_RegexTokenTest extends TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @param array<int, array{def: string, op: string, val: string}> $definitions
   * @return void
   */
  private function setTokens($definitions) {
    CRM_Mailingtools_RegexToken::setTokenDefinitions($definitions);
  }

  /**
   * @return void
   */
  public function testReplacesEveryOccurrence() {
    $this->setTokens([
      ['def' => '\\{X\\}', 'op' => CRM_Mailingtools_RegexToken::OPERATOR_REPLACE, 'val' => 'REPLACED'],
    ]);

    self::assertSame(
      'a REPLACED b REPLACED c',
      CRM_Mailingtools_RegexToken::tokenReplace('a {X} b {X} c')
    );
  }

  /**
   * @return void
   */
  public function testIdentityTokenTerminates() {
    $this->setTokens([
      ['def' => 'foo', 'op' => CRM_Mailingtools_RegexToken::OPERATOR_REPLACE, 'val' => 'foo'],
    ]);

    self::assertSame('foo bar foo', CRM_Mailingtools_RegexToken::tokenReplace('foo bar foo'));
  }

  /**
   * @return void
   */
  public function testTextWithoutTokensIsUnchanged() {
    $this->setTokens([
      ['def' => '\\{X\\}', 'op' => CRM_Mailingtools_RegexToken::OPERATOR_REPLACE, 'val' => 'REPLACED'],
    ]);

    self::assertSame('nothing to do here', CRM_Mailingtools_RegexToken::tokenReplace('nothing to do here'));
  }

}

<?php

namespace Sk\SmartId\Api\Data;

use PHPUnit\Framework\TestCase;

class SemanticsIdentifierTest extends TestCase
{

  /**
   * @test
   */
  public function validate_estonian()
  {
    $this->assertNull( SemanticsIdentifier::fromString("PNOEE-30303039816")->validate());
  }

  /**
   * @test
   */
  public function validate_latvian()
  {
    $this->assertNull( SemanticsIdentifier::fromString("PNOLV-030303-10012")->validate());
  }

  /**
   * @test
   */
  public function validate_lithuanian()
  {
    $this->assertNull( SemanticsIdentifier::fromString("PNOLT-30303039816")->validate());
  }

  /**
   * Example @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
   *
   * @test
   */
  public function validate_example1FromSpec()
  {
    $this->assertNull( SemanticsIdentifier::fromString("PASSK-P3000180")->validate());
  }

  /**
   * Example @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
   *
   * @test
   */
  public function validate_example2FromSpec()
  {
    $this->assertNull( SemanticsIdentifier::fromString("IDCBE-590082394654")->validate());
  }

  /**
   * Example @described https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf in chapter 5.1.3
   *
   * @test
   */
  public function validate_example3FromSpec()
  {
    $this->assertNull( SemanticsIdentifier::fromString("EI:SE-200007292386")->validate());
  }

}

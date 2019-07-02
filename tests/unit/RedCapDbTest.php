<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

class RedCapDbTest extends TestCase
{
    public function setup()
    {
    }

    public function testCreate()
    {
        $redCapDb = new RedCapDb();
        $this->assertNotNull($redCapDb);
    }
}

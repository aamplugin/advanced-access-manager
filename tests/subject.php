<?php

class TestSubject extends AAM_Core_Subject {

    public function getObject($type, $id = 0, $param = null) {
        $object = parent::getObject($type, $id, $param);
    }
}
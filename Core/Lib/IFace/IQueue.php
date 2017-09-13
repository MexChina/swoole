<?php
namespace Swoole\Core\Lib\IFace;

interface IQueue
{
    function push($data);
    function pop();
}
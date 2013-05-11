<?php
interface ITransactional{
	public function runQueue($queue=NULL);
}
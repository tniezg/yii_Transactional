<?php
class Transactional{
	/**
	To stop the queue, a function call has to explicitly return false.
	Returns index of last successful operation
	*/
	public function runQueue($queue=NULL){
		$transactionAlreadyRunning=false;
		if(is_null($queue)){
			throw new Exception('no queue provided');
		}
		try{
			$transaction = Yii::app()->db->beginTransaction();
		}catch(PDOException $e){
			$transactionAlreadyRunning=true;
		}
		$index=0;
		try{
			while($index<count($queue)){
				$item=$queue[$index];
				$itemCallResult;
				// First argument is the scope, second the function name,
				// arguments are the rest.
				if(!is_array($item)){
					throw new DomainException('Only arrays allowed as items');
				}
				if(count($item)<2){
					throw new DomainException('Number of arguments for each queue item must be at least 2');
				}
				$scope=array_shift($item);
				$methodName=array_shift($item);
				$arguments=$item;
				if(is_callable(array($scope,$methodName))){
					$itemCallResult=call_user_func_array(array($scope,$methodName), $arguments);
				}else{
					throw new DomainException('Method '.$methodName.' not available');
				}
				if($itemCallResult!==false){
					$index++;
				}else{
					break;
				}
			}
			if($index<count($queue)){
				if(!$transactionAlreadyRunning)
					$transaction->rollBack();
				return $index;
			}else{
				if(!$transactionAlreadyRunning)
					$transaction->commit();
				return $index;
			}
		}catch(Exception $e){
			if(!$transactionAlreadyRunning)
				$transaction->rollBack();
			echo $e;
			return $index;
		}
	}
}
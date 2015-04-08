<?php 

use \PHPParser_Node;
use \PHPParser_Node_Stmt;
use \PHPParser_Node_Expr;
use \PHPParser_Node_Name;
use \PHPParser_Node_Scalar;

namespace wardormeur\phpbbwpunicorn;

class SafeFunction extends \PHPParser_NodeVisitorAbstract
{
	private $functions;
	public function __construct(array $functions){
		$this->functions = $functions;
	}



    public function leaveNode(\PHPParser_Node $node) {
        $is_function = array_search($node->name,$this->functions);
		if ( $node instanceof Stmt\Function_ && $is_function!== FALSE){	
			$node = $this->encapsulate($node);
			$this->removeFunction($is_function);
			return $node;
		}
	}
	
	private function encapsulate($node){
		$encapsulated_node = 
			new PHPParser\Node\Stmt\If_(
				new Expr\BooleanNot(
					new PHPParser\Node\Expr\FuncCall(
						new Name\FullyQualified('function_exists'),
						[
							new Node\Arg(new Scalar\String_($node->name))
						]
					)
				)
				,[	
					'stmts' =>[$node]
				]
			);
			
			
			
		return $encapsulated_node;
	}
	
	private function removeFunction($function_index){
		unset($this->function[$function_index]);
	}
}
?>
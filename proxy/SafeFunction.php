<?php
namespace wardormeur\phpbbwpunicorn;

use \PHPParser_Node;
use \PHPParser_Node_Stmt;
use \PHPParser_Node_Expr;
use \PHPParser_Node_Name;
use \PHPParser_Node_Scalar;


class SafeFunction extends \PHPParser_NodeVisitorAbstract
{
	private $functions;
	public function __construct(array $functions){
		$this->functions = $functions;
	}



    public function leaveNode(\PHPParser_Node $node) {
        $is_function = array_search($node->name,$this->functions);
		if ( $node instanceof \PHPParser_Node_Stmt_Function && $is_function!== FALSE){
			$node = $this->encapsulate($node);
			$this->removeFunction($is_function);
			return $node;
		}
	}

	private function encapsulate($node){
		$encapsulated_node =
			new \PHPParser_Node_Stmt_If(
				new \PHPParser_Node_Expr_BooleanNot(
					new \PHPParser_Node_Expr_FuncCall(
						new \PHPParser_Node_Name_FullyQualified('function_exists'),
						[
							new \PHPParser_Node_Arg(new \PHPParser_Node_Scalar_String($node->name))
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

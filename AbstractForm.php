<?php
namespace Braghim\Zf2lib;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class AbstractForm extends Form
{
	private $inputFilters;
	
	public function __construct() {
		parent::__construct();
		$this->setAttribute("action", "");
		$this->setAttribute("method", "post");
		
		// Inicia e seta filtros e validacoes
		$this->inputFilters = new InputFilter();
		$this->setInputFilter($this->inputFilters);
		
		/**
		 * Todo formulario tem um botao submit ;)
		 */
		
		// clear button
		$this->add(array(
			'name' => 'clear',
			'type' => 'button',
			'attributes' => array(
				'class' => 'btn btn-default btn-xs',
				'type' => 'submit'
			),
		));
		
		// Submit button
		$this->add(array(
			'name' => 'submit',
			'type' => 'button',
			'attributes' => array(
				'class' => 'btn btn-success',
				'type' => 'submit'
			),
		));
	}
	
	/**
	 * Retorna mensagens do elemento do formulario em formato propicio para o tooltip do bootstrap.css
	 * 
	 * @param string $elementName
	 * @return string
	 */
	public function getTooltip($elementName) {
		$toolTip = "";
		
		$messages = parent::getMessages($elementName);
		if ($messages) {
			foreach ($messages as $msg) {
				$toolTip .= "<br/>* " . $msg;
			}
		}
		return ltrim($toolTip, "<br/>");
	}
	
	/**
	 * Retorna true caso haja mensagem de qualquer natureza no formulario
	 * 
	 * @return bool
	 */
	public function hasMessages()
	{
		return ((bool) parent::getMessages());
	}
	
	/**
	 * Retorna classe (css) para elementos que tem mensagem (erro)
	 * 
	 * @param string $element
	 * @param string $ifYes
	 * @return string
	 */
	public function hasMessage($element, $ifYes = 'alert-danger')
	{
		return ((bool) parent::getMessages($element)) ? $ifYes : '';
	}
	
	/**
	 * Adiciona filtro de validacao ao formulario.
	 * 
	 * @param array $filter
	 * @return \Fabrica\Form\AbstractForm
	 */
	public function addInputFilter(array $filter)
	{
		$this->inputFilters->add($filter);
		return $this;
	}
	
	/**
	 * Obriga a nao ser obrigatorio.
	 * 
	 * @param type $name
	 * @return \Fabrica\Form\AbstractForm
	 */
	protected function setNotRequired($name)
	{
		$this->addInputFilter(array('name' => $name, 'required' => false));
		return $this;
	}
}

<?php

namespace Mysamples\SimpleTpl;

class Tpl {
	private string $tplDir;
	private string $compileDir;
	private string $tplName;
	private array $vars = [];

	public function __construct(string $tplName, string $tplDir = './tpl/', string $compileDir = './ctpl/') {
		$this->tplName = $tplName;
		$this->tplDir = $tplDir;
		$this->compileDir = $compileDir;

		if (!$this->isTplExists()) {
			throw new \RuntimeException('Tpl not found.');
		}
	}

	public function setVar(string $name, $value): void {
		$this->vars[$name] = $value;
	}

	public function render(): string {
		$tpl = file_get_contents($this->getTplPathName());
		$compilePath = $this->compileDir . uniqid() . $this->tplName;
		if (false === file_put_contents($compilePath, $this->replaceVars($tpl))) {
			throw new \RuntimeException('Compile tpl unsuccessful');
		}
		ob_start();
		@include($compilePath);
		$result = ob_get_clean();
		unlink($compilePath);
		return $result;
	}

	private function replaceVars(string $tpl): string {
		preg_match_all('/{\s*([^}]+)}/s', $tpl, $terms);
		foreach ($terms[1] as $termKey => $term) {
			$blockIf = strtolower(trim(substr($term, 0, 3 )));
			$blockElse = strtolower(substr($term, 0, 4 ));

			if ('if' === $blockIf) {
				$condition = trim(substr($term, 2));
				$tpl = preg_replace('/' . preg_quote($terms[0][$termKey], '/'). '/', '<?php if (' . $this->getVar($condition) . ') { ?>', $tpl, 1);

			} elseif ('/if' === $blockIf) {
				$tpl = preg_replace('/' . preg_quote($terms[0][$termKey], '/'). '/', '<?php } ?>', $tpl, 1);

			} elseif ('else' === $blockElse && 4 === strlen(trim($term))) {
				$tpl = preg_replace('/' . preg_quote($terms[0][$termKey], '/'). '/', '<?php } else { ?>', $tpl, 1);

			} else {
				$tpl = preg_replace('/' . preg_quote($terms[0][$termKey], '/'). '/', $this->getVar(trim($term)), $tpl, 1);
			}
		}

		return $tpl;
	}

	private function getVar(string $varName) {
		if (array_key_exists($varName, $this->vars)) {
			return $this->vars[$varName];
		}
		throw new \RuntimeException('Variable ' . $varName . ' not found.');
	}

	private function isTplExists(): bool {
		if (file_exists($this->getTplPathName())) {
			return true;
		}
		return false;
	}

	private function getTplPathName(): string {
		return $this->tplDir . $this->tplName;
	}
}
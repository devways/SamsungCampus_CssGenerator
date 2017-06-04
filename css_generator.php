<?php
class css_generator {
	public $argc;
	public $argv;
	public $image_array = [];
	public $longeurSprit;
	public $hauteurSprit;
	public $reSizeValue;
	public $cssValue;
	public $pngValue;
	public $nameUrl;
	public $flag_error;


	public function __construct($argc, $argv) {
		$this->argc = $argc;
		$this->argv = $argv;
		$this->args = $argv[$argc - 1];
		$this->shortopt = "ri::s::p:o:c:";
		$this->longopt = array (
				"recursive",
				"output-image::",
				"output-style::",
				"padding:",
				"override-size:",
				"columns_number:",
				);
		$this->option = getopt($this->shortopt, $this->longopt);
		$this->padding_value = 0;
	}

	public function testOption() {
		$flag =0;
		foreach($this->argv as $key => $value) {
			if ($key != 0 && $key != ($this->argc - 1)) {
				if ($value[0] == "-" && $value[1] != "-") {
					for ($i = 1; $i < strlen($value); $i++) {
						if (!array_key_exists($value[$i], $this->option)) {
							if ($value[$i] == "=") {
								break;
							}
							else {
								echo "Invalid command : $value[$i]\n";
								$flag = 1;
							}
						}
						elseif (array_key_exists($value[$i], $this->option) && is_array($this->option[$value[$i]])) {
							$tmp = $value[$i];
							(string)$this->option[$tmp] = (string)end($this->option[$tmp]);
						}
					}
				}
				elseif ($value[0] == "-" && $value[1] == "-") {
					$str = "";
					for ($i = 2; $i < strlen($value); $i++) {
						if ($value[$i] == "=") {
							break;
						}
						$str .= $value[$i];
					}
					if (!array_key_exists($str, $this->option)) {
						echo "Invalid command : $str\n";
						$flag = 1;
					}
					elseif (array_key_exists($str, $this->option) && is_array($this->option[$str])) {
						(string)$this->option[$str] = (string)end($this->option[$str]);
					}
				}
				else {
					echo "Invalid command : $value\n";
					$flag = 1;
				}
			}
		}
		if ($flag == 1) {
			exit;
		}
	}

	public function getImage() {
		if (isset($this->option["r"]) || isset($this->option["recursive"])) {
			$this->getImageRecursiv($this->args);
		}
		else {
			$this->getImageNormal($this->args);
		}
	}

	public function isImage($pathImage) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$file_info = finfo_file($finfo, $pathImage);
		$str = "";
		for ($j = 0; $j < strlen($file_info); $j++) {
			$str .= $file_info[$j];
		}
		finfo_close($finfo);
		if ($str == "image/png") {
			return true;
		}
		else {
			return false;
		}
	}

	public function getImageNormal($pathImage) {
		$dir = $pathImage;
		$i = 0;
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					$pathImage = $dir . "/" . $file;
					if ($file[0] != "." && $this->isImage($pathImage)) {
						$this->image_array[$i] = $pathImage;
						$i++;
					}
				}
				closedir($dh);
			}
		}
	}

	public function getImageRecursiv($pathImage) {
		$dir = $pathImage;
		static $i = 0;
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file != "." && $file != "..") {
						$pathImage = $dir . "/" . $file;
						if (is_dir($pathImage)) {
							$this->getImageRecursiv($pathImage);
						}
						elseif ($file[0] != "." && $this->isImage($pathImage)) {
							$this->image_array[$i] = $pathImage;
							$i++;
						}
					}
				}
				closedir($dh);
			}
		}
	}

	public function getSize() {
		$longeurMemory = 0;
		$hauteurMemory = 0;
		foreach($this->image_array as $key => $value) {
			$longeur = 0;
			$hauteur = 0;
			foreach($this->image_array[$key] as $key2 => $value2) {
				$tmp_value2 = imagecreatefrompng($value2);
				$numberPadding = $key2;
				if (isset($this->option["override-size"]) && is_numeric($this->option["override-size"]) && $this->option["override-size"] != 0) {
					$longeur += $this->option["override-size"];
					$hauteur = $this->option["override-size"];
				}
				elseif (isset($this->option["o"]) && is_numeric($this->option["o"]) && $this->option["o"] != 0) {
					$longeur += $this->option["o"];
					$hauteur = $this->option["o"];
				}
				else {
					$longeur += imagesx($tmp_value2);
					if (imagesy($tmp_value2) > $hauteur) {
						$hauteur = imagesy($tmp_value2);
					}
				}
			}
			$longeur += ($this->padding_value * ($numberPadding));
			if ($longeurMemory < $longeur) {
				$longeurMemory = $longeur;
			}
			$hauteurMemory += $hauteur;
		}
		$this->longeurSprit = $longeurMemory;
		$this->hauteurSprit = $hauteurMemory;
	}

	public function createSprite() {
		$dest = imagecreatetruecolor($this->longeurSprit, $this->hauteurSprit);
		$positionY = 0;
		$css = "";
		foreach ($this->image_array as $key => $value) {
			$positionX = 0;
			$hauteurMaxLine = 0;
			foreach($this->image_array[$key] as $key2 => $value2) {
				$tmp_value2 = imagecreatefrompng($value2);
				if ((isset($this->option["o"]) && is_numeric($this->option["o"]) && $this->option["o"] != 0) ||
						(isset($this->option["override-size"]) && is_numeric($this->option["override-size"]) && $this->option["override-size"] != 0)) {
					imagecopyresampled($dest , $tmp_value2, $positionX, $positionY, 0, 0, $this->reSizeValue, $this->reSizeValue, imagesx($tmp_value2), imagesy($tmp_value2));
					$css .= "." . "image" . $key . $key2 .  "{" . "\n";
					$css .= "    " . "background: url(" . $this->nameUrl . ") no-repeat;\n";
					$css .= "    " . "width: $this->reSizeValue" . "px;\n";
					$css .= "    " . "height: $this->reSizeValue" . "px;\n";
					$css .= "    " . "background-position: -$positionX" . "px -$positionY" . "px;\n" . "}\n\n";

					$positionX += ($this->reSizeValue + $this->padding_value);
					$hauteurMaxLine = $this->reSizeValue;
				}
				else {
					imagecopymerge($dest , $tmp_value2, $positionX, $positionY, 0, 0, imagesx($tmp_value2), imagesy($tmp_value2), 100);
					$css .= "." . "image" . $key . $key2 .  "{" . "\n";
					$css .= "    " . "background: url(" . $this->nameUrl . ") no-repeat;\n";
					$css .= "    " . "width:" . imagesx($tmp_value2) . "px;\n";
					$css .= "    " . "height:" . imagesy($tmp_value2) . "px;\n";
					$css .= "    " . "background-position: -" . $positionX . "px -" . $positionY . "px;\n" . "}\n\n";

					$positionX += (imagesx($tmp_value2) + $this->padding_value);
					if (imagesy($tmp_value2) > $hauteurMaxLine) {
						$hauteurMaxLine = imagesy($tmp_value2);
					}
				}
			}
			$positionY += $hauteurMaxLine;
		}
		imagepng($dest, $this->nameUrl);
		file_put_contents($this->cssValue, $css);
	}

	public function getNameUrl() {
		if (isset($this->option["i"]) || isset($this->option["output-image"])) {
			if ($this->pngValue != null) {
				$this->nameUrl = $this->pngValue . ".png";
			}
			else {
				$this->nameUrl = "sprite.png";
			}
		}
		else {
			$this->nameUrl = "sprite.png";
		}
	}

	public function getPng() {
		if (isset($this->option["output-image"])) {
			$this->pngValue = $this->option["output-image"];
		}
		elseif (isset($this->option["i"]) ) {
			$this->pngValue = $this->option["i"];
		}
	}

	public function getCss() {
		if (isset($this->option["output-style"])) {
			$this->cssValue = $this->option["output-style"] . ".css";
		}
		elseif (isset($this->option["s"])) {
			$this->cssValue = $this->option["s"] . ".css";
		}
		else {
			$this->cssValue = "style.css";
		}
	}

	public function getReSize() {
		if (isset($this->option["override-size"]) && is_numeric($this->option["override-size"]) && $this->option["override-size"] != 0) {
			$this->reSizeValue = $this->option["override-size"];
		}
		elseif(isset($this->option["o"]) && is_numeric($this->option["o"]) && $this->option["o"] != 0) {
			$this->reSizeValue = $this->option["o"];
		}
		elseif((isset($this->option["override-size"]) && $this->option["override-size"] == 0) || (isset($this->option["o"]) && $this->option["o"] == 0))  {
			$this->flag_error = 1;
		}
	}

	public function getPadding() {
		if (isset($this->option["padding"]) && is_numeric($this->option["padding"])) {
			$this->padding_value = $this->option["padding"];
		}
		elseif (isset($this->option["p"]) && is_numeric($this->option["p"])) {
			$this->padding_value = $this->option["p"];
		}
		else {
			$this->padding_value = 0;
		}
	}

	public function getLimit() {
		if (isset($this->option["columns_number"]) && is_numeric($this->option["columns_number"])) {
			$nbr = $this->option["columns_number"];
		}
		elseif (isset($this->option["c"]) && is_numeric($this->option["c"])) {
			$nbr = $this->option["c"];
		}
		elseif((isset($this->option["c"]) && !is_numeric($this->option["c"]))||
				(isset($this->option["columns_number"]) && !is_numeric($this->option["columns_number"])))  {
			$nbr = 0;
			$this->flag_error = 1;
		}
		else {
			$nbr = 1;
		}
		$array_temp = [];
		$j = 0;
		$k = 0;
		for ($i = 0; $i < count($this->image_array); $i++) {
			$array_temp[$j][$k] = $this->image_array[$i];
			$k++;
			if ($nbr != 0 && ($i + 1) % $nbr === 0) {
				$j++;
				$k = 0;
			}
			elseif ($nbr == 0) {
				echo "Invalid value : 0\n";
				$this->flag_error = 1;
			}
		}
		$this->image_array = $array_temp;
	}

	public function lancement() {
		if ($this->argc > 0 && is_dir($this->args)) {
			$this->testOption();
			$this->getImage();
			$this->getLimit();
			$this->getPadding();
			$this->getReSize();
			$this->getSize();
			$this->getPng();
			$this->getCss();
			$this->getNameUrl();
			if ($this->flag_error == 0) {
				$this->createSprite();
			}
		}
		else {
			echo "fichier repertoire invalid\n";
		}
	}
}

$obj = new css_generator($argc, $argv);
$obj->lancement();

<?php
// Render.class.php から呼び出し

	function getthumbpath($img_src)
	{
		global $xoopsModuleConfig;
		$maxsize = $xoopsModuleConfig['thumbnail_width'];
		$quality = 80;// 画質 0〜100

		$photos_path = XOOPS_ROOT_PATH.$img_src[1];

		// 念の為、最大幅600pxに制限
		if ($maxsize > 600)
		{
			$maxsize = 600;
		}

		// 画像の存在チェック
		if (!file_exists($photos_path))
		{
			return false;
		}

//		if (!preg_match('/[\S|\s+]+([\S|\s]+)\.([a-zA-Z]+)/', $photos_path, $matches))
//		{
//			return false;
//		}
//		$photos_name = $matches[1];
//		$photos_type = $matches[2];
		$photos_url = XOOPS_URL.$img_src[1];
//		$thumbs_name = $photos_name.'.'.$photos_type;
// basename()を使用するように修正 thanks wye
		$thumbs_name = basename(dirname($photos_path)).basename($photos_path);
		$thumbs_path = XOOPS_ROOT_PATH.$xoopsModuleConfig['thumbnail_path'].'/'.$thumbs_name;
		$thumbs_url = XOOPS_URL.$xoopsModuleConfig['thumbnail_path'].'/'.$thumbs_name;

		// 既にサムネイル画像がある場合はパスを返す
		if (file_exists($thumbs_path))
		{
			return '<img src="'.$thumbs_url.'" alt="image" />';
		}

		// 画像サイズ取得
		$image_size = @getimagesize($photos_path);

		$photos_width = $image_size[0];
		$photos_height = $image_size[1];
		$image_type = $image_size[2];

		if ($photos_width <= $maxsize && $image_size[1] <= $maxsize)
		{
			$thumbs_width = $photos_width;
			$thumbs_height = $photos_height;
			$resize = false;
		}
		else
		{
			if ($photos_width >= $photos_height)
			{
				$aspectratio = $photos_width / $maxsize;
				$thumbs_width = $maxsize;
				$thumbs_height = round($photos_height / $aspectratio, 0);
			}
			else
			{
				$aspectratio = $photos_height / $maxsize;
				$thumbs_width = round($photos_width / $aspectratio, 0);
				$thumbs_height = $maxsize;
			}
			$resize = true;
		}

		if (!$resize)
		{
			copy($photos_path, $thumbs_path);
			return '<img src="'.$thumbs_url.'" alt="image" />';
		}

		$imagecreate = function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
		$imageresize = function_exists('imagecopyresampled') ? 'imagecopyresampled' : 'imagecopyresized';

		if ( $image_type == 1 && ImageTypes() & IMG_GIF )
		{
			$photos_image = ImageCreateFromGif($photos_path);
			$type = 'imagegif';
		}
		elseif ( $image_type == 2 && ImageTypes() & IMG_JPG )
		{
			$photos_image = ImageCreateFromJpeg($photos_path);
			$type = 'imagejpeg';
		}
		elseif ( $image_type == 3 && ImageTypes() & IMG_PNG )
		{
			$photos_image = ImageCreateFromPng($photos_path);
			$type = 'imagepng';
		}
		else
		{
			return '<img src="'.$photos_url.'" alt="image" />';
		}

		$thumbs_image = @$imagecreate($thumbs_width, $thumbs_height);
		@$imageresize($thumbs_image, $photos_image, 0, 0, 0, 0, $thumbs_width, $thumbs_height, $photos_width, $photos_height);

		if ($image_type == 2)
		{
			@$type($thumbs_image, $thumbs_path, $quality);
		}
		else
		{
			@$type($thumbs_image, $thumbs_path);
		}

		@imagedestroy($photos_image);
		@imagedestroy($thumbs_image);
		return '<img src="'.$thumbs_url.'" alt="image" />';
	}

?>

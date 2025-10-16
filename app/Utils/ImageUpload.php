<?php

namespace App\Utils;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;
use Intervention\Image\Facades\Image;

class ImageUpload
{
    /**
     * $file 图片信息
     * $folder 图片存储文件夹（必须以 storage 开头，因为软链对应 public 目录下的 storage 目录);
     * $name 正常的存储，如果设置了 max_width 则图片超过了这个宽度才进行裁剪。等同于 raw。
     * $max_width 设置该值，图片超过该长度时，会进行裁剪
     * $raw_name raw 图片的名字。设置了该值，才会额外存该文件（注意组合）
     * $thumbnail_name 小图名字。
     * $min_width 小图最大宽度（按道理说，设置了小图一定要设置小图的宽度的)
     * $need_exif 是否需要 exif 信息
     * $watermark 是否需要加水印（为了美观好看，水印都不加了)
     *
     * 其实，上传瞬间才会用到三种图片（原图、压缩图[可能会]，缩略图）。
     */
    public function saveBinaryImage($file, $folder, $name, $max_width = null, $raw_name = null, $thumbnail_name = null, $min_width = null, $need_exif = false, $watermark = false)
    {
        // 文件具体存储的物理路径，`public_path()` 获取的是 `public` 文件夹的物理路径。
        $upload_path = public_path() . '/' . $folder;

        // 创建文件夹（如果没权限则会报错）
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $upload_file = '';
        if ($raw_name) {
            $upload_file = $upload_path . $raw_name;
            $raw = $folder . $raw_name;
        } else {
            $upload_file = $upload_path . $name;
            $raw = '';
            $url = $folder . $name;
        }

        $result = file_put_contents($upload_file, $file, true);
        if ($result == false) {
            return ['error' => '写入文件失败，可能没有权限'];
        }

        // 如果需要 exif 信息才去查
        $exifId = null;
        if ($need_exif) {
            try {
                $img_info = getimagesize($upload_file);
                $exif = exif_read_data($upload_file);
                $exif['width'] = $img_info[0];
                $exif['height'] = $img_info[1];
                $exifId = $this->cacheExif($exif);
            } catch (Exception $e) {
                Log::channel('single')->error('exif error', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            }
        }

        if ($raw_name && $name) {
            // 相当于复制
            $this->reduceSize($upload_file, null, $upload_path . $name);

            $url = $folder . $name;
        }

        // 如果限制了图片宽度，就进行裁剪
        if ($max_width && $max_width > 0) {

            // 先看看图片的宽度是否币这个小
            $img_info = getimagesize($upload_path . $name);
            $width = $img_info[0];
            $height = $img_info[1];
            $do_width = max($width, $height);
            // 如果有需要才进行裁剪
            if ($do_width > $max_width) {
                // 图片宽大于高
                if ($width >= $height) {
                    $this->reduceSize($upload_path . $name, $max_width);
                } else {
                    $this->reduceSize($upload_path . $name, (int)($max_width * $width / $height));
                }
            }
        }

        // 缩略图
        if ($min_width && $min_width > 0 && $thumbnail_name) {
            $this->reduceSize($upload_file, $min_width, $upload_path . $thumbnail_name);
            $thumbnail = $folder . $thumbnail_name;
        } else {
            $thumbnail = '';
        }
        return ['error' => false, 'raw' => $raw, 'url' => $url, 'thumbnail' => $thumbnail, 'exifId' => $exifId];
    }

    private function cacheExif($exif)
    {
        Log::channel('single')->info('cacheExif', ['exif' => $exif]);
        // 如果存在 exif 信息，就存 10 分钟
        if ($exif && isset($exif['Make']) && isset($exif['Model']) && isset($exif['ExposureTime']) && $exif['Make'] && $exif['Model'] && $exif['ExposureTime']) {
            $fnum = $exif['FNumber'];
            $fnumArr = explode('/', $fnum);
            if (count($fnumArr) == 2) {
                $aperture = $fnumArr[0] / $fnumArr[1];
            } else {
                $aperture = $fnum;
            }

            $data = [
                'artist' => isset($exif['Artist']) ? $exif['Artist'] : null,
                'make' => $exif['Make'],
                'model' => $exif['Model'],
                'exposure_time' => $exif['ExposureTime'],
                'aperture' => $aperture,
                'iso' => $exif['ISOSpeedRatings'],
                'focus' => (int)$exif['FocalLength'],
                'camera' => $exif['UndefinedTag:0xA434'] ?? '',
                'flash' => $exif['Flash'],
                'shoot_at' => $exif['DateTimeOriginal'] ?? $exif['DateTime'],
                'width' => $exif['width'] ?? '',
                'height' => $exif['height'] ?? '',
            ];
            $id = session_create_id();
            Redis::hmset($id, $data);
            Redis::expire($id, 600);
            return $id;
        }
        return null;
    }

    // 保存 input file(2023-06-19 这里需要改造，改造前使用不合适 todo)
    public function saveFileImage($file, $folder, $name, $max_width = null, $thumbnail_name = null, $min_width = null)
    {
        // 文件具体存储的物理路径，`public_path()` 获取的是 `public` 文件夹的物理路径。
        // 值如：/home/vagrant/Code/larabbs/public/uploads/images/avatars/201709/21/
        $upload_path = public_path() . '/' . $folder;

        // 获取文件的后缀名，因图片从剪贴板里黏贴时后缀名为空，所以此处确保后缀一直存在
        if (strtolower($file->getClientOriginalExtension())) {
            $extension = strtolower($file->getClientOriginalExtension());
        } else {
            $extension = 'png';
        }
        
        // 构建存储的文件夹规则，值如：uploads/images/avatars/201709/21/
        // 文件夹切割能让查找效率更高。
        $file_name = sprintf('%s.%s', $name, $extension);

        // 将图片移动到我们的目标存储路径中
        $file->move($upload_path, $file_name);

        // 如果限制了图片宽度，就进行裁剪
        if ($max_width && $max_width > 0 && $extension != 'gif') {

            // 此类中封装的函数，用于裁剪图片
            $this->reduceSize($upload_path . '/' . $file_name, $max_width);
        }

        // 缩略图
        if ($min_width && $min_width > 0 && $thumbnail_name && $extension != 'gif') {
            $oriPath = sprintf('%s/%s', $upload_path, $file_name);
            $newName = sprintf('%s.%s', $thumbnail_name, $extension);
            $newPath = sprintf('%s/%s', $upload_path, $newName);
            $this->reduceSize($oriPath, $min_width, $newPath);
            return ['url' => $folder . $file_name, 'thumbnail' => $folder . $newName];
        }

        return ['url' => $folder . $file_name, 'thumbnail' => ''];
    }

    // 保存 base64 (2023-06-19 这里需要改造，改造前使用不合适 todo)
    public function saveBase64Image($file, $folder, $name, $max_width = null, $thumbnail_name = null, $min_width = null)
    {
        $upload_path = public_path() . '/' . $folder;
        try {
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
        } catch (Exception $e) {
            Log::info('saveBase64Image-error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
        }

        if (stripos($file, 'data:image/jpeg;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/jpeg;base64,', '', $file));
        } elseif (stripos($file, 'data:image/png;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/png;base64,', '', $file));
        } else {
            return ['error' => '非图片文件'];
        }

        $result = file_put_contents($upload_path . $name, $img); //返回的是字节数
        if ($result == false) {
            return ['error' => '写入文件失败，可能没有权限'];
        }

        // 如果限制了图片宽度，就进行裁剪
        if ($max_width && $max_width > 0) {
            // 此类中封装的函数，用于裁剪图片
            $this->reduceSize($upload_path . $name, $max_width);
        }
 
        // 缩略图
        if ($min_width && $min_width > 0 && $thumbnail_name) {
            $this->reduceSize($upload_path . $name, $min_width, $upload_path . $thumbnail_name);
            return ['url' => $folder . $name, 'thumbnail' => $folder . $thumbnail_name];
        }
        return ['url' => $folder . $name, 'thumbnail' => ''];
    }

    private function reduceSize($file_path, $max_width = null, $new_path = null)
    {
        Log::info('filepath', ['filepath' => $file_path, 'newpath' => $new_path]);

        // 先实例化，传参是文件的磁盘物理路径
        $image = Image::make($file_path);

        if ($max_width) {
            // 进行大小调整的操作
            $image->resize($max_width, null, function ($constraint) {
                // 设定宽度是 $max_width，高度等比例双方缩放
                $constraint->aspectRatio();
                // 防止裁图时图片尺寸变大
                $constraint->upsize();
            });
        }

        // 对图片修改后进行保存
        if ($new_path) {
            $image->save($new_path);
        } else {
            $image->save();
        }
    }
}

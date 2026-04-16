### 启动容器

```
docker run --rm --name ztbg-php --network ycsjnet -it -v /d/Work/Code/智探宝馆/zhitanbaoguan.laravel:/www/ztbg.laravel -w /www/ztbg.laravel holovision-php:8.2 bash
```


### oss 环境变量导入

[https://help.aliyun.com/zh/oss/developer-reference/manual-for-php-v2/?spm=5176.8466032.console-base_help.dexternal.33e91450j538VP](https://help.aliyun.com/zh/oss/developer-reference/manual-for-php-v2/?spm=5176.8466032.console-base_help.dexternal.33e91450j538VP)

```
echo "export OSS_ACCESS_KEY_ID='YOUR_ACCESS_KEY_ID'" >> ~/.bashrc
echo "export OSS_ACCESS_KEY_SECRET='YOUR_ACCESS_KEY_SECRET'" >> ~/.bashrc

source ~/.bashrc

echo $OSS_ACCESS_KEY_ID
echo $OSS_ACCESS_KEY_SECRET
```
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 添加这个：强制在 CLI 输出详细错误
        $this->renderable(function (Throwable $e, $request) {
            if (app()->runningInConsole()) {
                throw $e; // 重新抛出，让 PHP 打印完整堆栈
            }
        });
    }

    // 关键：覆盖 renderForConsole 方法
    public function renderForConsole($output, Throwable $e): void
    {
        // 打印完整堆栈跟踪
        $output->writeln('<error>' . get_class($e) . ': ' . $e->getMessage() . '</error>');
        $output->writeln('');
        $output->writeln('<comment>Stack trace:</comment>');
        $output->writeln($e->getTraceAsString());

        // 或者直接用 PHP 默认的异常处理
        throw $e;
    }
}

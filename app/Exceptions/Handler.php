<?php

namespace App\Exceptions;

use Exception;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\HttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $exception)
    {
        /** @var TYPE_NAME $exception */
        if ($exception instanceof ValidationException){
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof ModelNotFoundException){
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("{$modelName} Não encontrado", 404);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof AuthenticationException){
            return $this->unauthenticated($request, $exception);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof AuthorizationException){
            return $this->errorResponse($request, 403);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof MethodNotAllowedHttpException){
            return $this->errorResponse("Médodo não permitido", 404);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof NotFoundHttpException){
            return $this->errorResponse("URL não encontrada", 404);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof HttpException){
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof RelationNotFoundException){
            return $this->errorResponse("Não foi possível encontrar um relacionamento", 409);
        }

        /** @var TYPE_NAME $exception */
        if ($exception instanceof QueryException){
            $errorCode = $exception->errorInfo[1];

            if ($errorCode == 1451){
                return $this->errorResponse('Não é possível remover este registro permanentemente poque há um relacionamento com outro registro', 409);
            }
        }

        if (config('app.debug')) {
            /** @var TYPE_NAME $request */
            /** @var TYPE_NAME $exception */
            return parent::render($request, $exception);
        }

        return $this->errorResponse("Erro inesperado. Por favor, tente novamente mais tarde", 500);
        }

        /**
         * Converte uma exceção de autenticação em uma resposta não autenticada.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Illuminate\Auth\AuthenticationException  $exception
         * @return \Illuminate\Http\Response
         */
        protected function unauthenticated($request, AuthenticationException $exception)
        {
            return $this->errorResponse("Não Autenticado", 401);
            return redirect()->guest(route('login'));
        }

        /**
         * Crie um objeto de resposta a partir da exceção de validação fornecida.
         *
         * @param  \Illuminate\Validation\ValidationException  $e
         * @param  \Illuminate\Http\Request  $request
         * @return \Symfony\Component\HttpFoundation\Response
         */
        protected function convertValidationExceptionToResponse(ValidationException $e, $request)
        {
            $errors = $e->validator->errors()->getMessages();

            return $this->errorResponse($errors, 422);
        }
}

@echo off
setlocal EnableExtensions DisableDelayedExpansion
title Re.Source - Ambiente de Desenvolvimento Local
color 0A

set "PROJECT_NAME=re.source"
set "REPOSITORY_URL=https://github.com/ddsc-sts/re.source.git"
set "XAMPP_DIR=C:\xampp"
set "HTDOCS_DIR=%XAMPP_DIR%\htdocs"
set "PROJECT_DIR=%HTDOCS_DIR%\%PROJECT_NAME%"
set "MYSQLD_EXE=%XAMPP_DIR%\mysql\bin\mysqld.exe"
set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "MYSQLADMIN_EXE=%XAMPP_DIR%\mysql\bin\mysqladmin.exe"
set "HTTPD_EXE=%XAMPP_DIR%\apache\bin\httpd.exe"
set "HTTPD_CONF=%XAMPP_DIR%\apache\conf\httpd.conf"
set "DB_NAME=resource"
rem Use 1 somente para apagar e recriar o banco de demonstracao.
set "RESET_DATABASE=0"
set "HTTP_PORT="
set "APP_URL="
set "STARTED_MYSQL=0"
set "STARTED_APACHE=0"

echo ==========================================
echo       INICIANDO AMBIENTE RE.SOURCE
echo ==========================================
echo.

if not exist "%XAMPP_DIR%\" ( echo [ERRO] XAMPP nao encontrado em "%XAMPP_DIR%". & goto :failure )
if not exist "%MYSQLD_EXE%" ( echo [ERRO] mysqld.exe nao encontrado. & goto :failure )
if not exist "%MYSQL_EXE%" ( echo [ERRO] mysql.exe nao encontrado. & goto :failure )
if not exist "%MYSQLADMIN_EXE%" ( echo [ERRO] mysqladmin.exe nao encontrado. & goto :failure )
if not exist "%HTTPD_EXE%" ( echo [ERRO] httpd.exe nao encontrado. & goto :failure )
if not exist "%HTTPD_CONF%" ( echo [ERRO] httpd.conf nao encontrado. & goto :failure )

echo [1/5] Verificando o projeto em htdocs...
if not exist "%PROJECT_DIR%\" (
    where git >nul 2>&1
    if errorlevel 1 ( echo [ERRO] Git nao foi encontrado. Instale o Git e execute novamente. & goto :failure )
    echo       Clonando %REPOSITORY_URL%...
    git clone "%REPOSITORY_URL%" "%PROJECT_DIR%"
    if errorlevel 1 ( echo [ERRO] Nao foi possivel clonar o repositorio. & goto :failure )
) else (
    echo       Projeto ja existe; o clone foi mantido.
)

if not exist "%PROJECT_DIR%\.env" (
    if not exist "%PROJECT_DIR%\.env.example" ( echo [ERRO] .env.example nao foi encontrado no projeto. & goto :failure )
    copy /Y "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
    echo       Arquivo .env criado com a configuracao local padrao.
)

echo [2/5] Iniciando MySQL...
"%MYSQLADMIN_EXE%" --host=127.0.0.1 --port=3306 --user=root ping --silent >nul 2>&1
if errorlevel 1 (
    start "Re.Source MySQL" /MIN "%MYSQLD_EXE%"
    set "STARTED_MYSQL=1"
    call :wait_for_mysql
    if errorlevel 1 goto :failure
) else echo       MySQL ja estava em execucao.

echo [3/5] Preparando banco de dados...
set "DATABASE_EXISTS=0"
"%MYSQL_EXE%" --host=127.0.0.1 --port=3306 --user=root -e "USE `%DB_NAME%`;" >nul 2>&1
if not errorlevel 1 set "DATABASE_EXISTS=1"
if "%RESET_DATABASE%"=="1" (
    echo       Apagando o banco %DB_NAME% para um reset completo...
    "%MYSQL_EXE%" --host=127.0.0.1 --port=3306 --user=root -e "DROP DATABASE IF EXISTS `%DB_NAME%`;"
    if errorlevel 1 goto :failure
    set "DATABASE_EXISTS=0"
)
if "%DATABASE_EXISTS%"=="1" (
    echo       Banco %DB_NAME% ja existe; importacao ignorada para preservar os dados.
) else (
    call :import_sql "%PROJECT_DIR%\database\seeders\re.sourcebanco.sql" || goto :failure
    call :import_sql "%PROJECT_DIR%\database\inserts\create_admin.sql" || goto :failure
    call :import_sql "%PROJECT_DIR%\database\inserts\empresa_demo.sql" || goto :failure
    call :import_sql "%PROJECT_DIR%\database\inserts\produto.sql" || goto :failure
    call :import_sql "%PROJECT_DIR%\database\inserts\saldo_demo.sql" || goto :failure
)

echo [4/5] Iniciando Apache...
tasklist /FI "IMAGENAME eq httpd.exe" /NH | findstr /I "httpd.exe" >nul
if errorlevel 1 (
    call :read_apache_port || goto :failure
    call :port_is_free %%HTTP_PORT%%
    if errorlevel 1 call :find_free_port
    if errorlevel 1 goto :failure
    call :configure_apache_port %%HTTP_PORT%% || goto :failure
    "%HTTPD_EXE%" -t >nul 2>&1
    if errorlevel 1 ( echo [ERRO] A configuracao do Apache e invalida. Verifique "%HTTPD_CONF%". & goto :failure )
    start "Re.Source Apache" /MIN "%HTTPD_EXE%"
    set "STARTED_APACHE=1"
    timeout /t 3 /nobreak >nul
) else (
    echo       Apache ja estava em execucao.
    call :read_apache_port || goto :failure
)

set "APP_URL=http://localhost:%HTTP_PORT%/%PROJECT_NAME%"
call :configure_app_url || goto :failure
for /f %%P in ('powershell -NoProfile -Command "$child=Get-CimInstance Win32_Process -Filter ('ProcessId=' + $PID); (Get-CimInstance Win32_Process -Filter ('ProcessId=' + $child.ParentProcessId)).ParentProcessId"') do set "CMD_PID=%%P"
start "Re.Source Cleanup Monitor" /MIN powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -Command "$parentProcessId=%CMD_PID%; $stopMySql=%STARTED_MYSQL%; $stopApache=%STARTED_APACHE%; while (Get-Process -Id $parentProcessId -ErrorAction SilentlyContinue) { Start-Sleep -Seconds 1 }; if ($stopApache -eq 1) { Get-Process -Name httpd -ErrorAction SilentlyContinue | Stop-Process -Force }; if ($stopMySql -eq 1) { Get-Process -Name mysqld -ErrorAction SilentlyContinue | Stop-Process -Force }" >nul 2>&1

echo [5/5] Abrindo o navegador...
start "" "%APP_URL%"
echo.
echo Ambiente pronto em %APP_URL%
echo Feche esta janela ou pressione qualquer tecla para encerrar os servicos iniciados pelo script.
pause >nul
goto :cleanup

:wait_for_mysql
set "MYSQL_TRIES=0"
:wait_for_mysql_loop
"%MYSQLADMIN_EXE%" --host=127.0.0.1 --port=3306 --user=root ping --silent >nul 2>&1
if not errorlevel 1 exit /b 0
set /a MYSQL_TRIES+=1
if %MYSQL_TRIES% GEQ 20 ( echo [ERRO] MySQL nao respondeu em 20 segundos. & exit /b 1 )
timeout /t 1 /nobreak >nul
goto :wait_for_mysql_loop

:import_sql
if not exist "%~1" ( echo [ERRO] Arquivo SQL nao encontrado: "%~1" & exit /b 1 )
echo       Importando %~nx1
"%MYSQL_EXE%" --host=127.0.0.1 --port=3306 --user=root --default-character-set=utf8mb4 < "%~1"
if errorlevel 1 ( echo [ERRO] Falha ao importar %~nx1. & exit /b 1 )
exit /b 0

:read_apache_port
set "HTTP_PORT="
for /f "tokens=2" %%P in ('findstr /R /C:"^[ ]*Listen [0-9][0-9]*" "%HTTPD_CONF%"') do set "HTTP_PORT=%%P"
if not defined HTTP_PORT ( echo [ERRO] Nao foi encontrada uma diretiva Listen valida em "%HTTPD_CONF%". & exit /b 1 )
exit /b 0

:port_is_free
netstat -ano | findstr /R /C:":%~1 .*LISTENING" >nul
if errorlevel 1 exit /b 0
exit /b 1

:find_free_port
for %%P in (80 8080 8081 8082 8083 8084 8085 8888) do (
    call :port_is_free %%P
    if not errorlevel 1 ( set "HTTP_PORT=%%P" & exit /b 0 )
)
echo [ERRO] Nenhuma porta livre foi encontrada para o Apache.
exit /b 1

:configure_apache_port
if not exist "%HTTPD_CONF%.re.source.backup" copy /Y "%HTTPD_CONF%" "%HTTPD_CONF%.re.source.backup" >nul
powershell -NoProfile -ExecutionPolicy Bypass -Command "$config='%HTTPD_CONF%'; $port=%~1; $content=Get-Content -Raw -LiteralPath $config; if ($content -notmatch '(?m)^\s*Listen\s+\d+') { exit 1 }; $content=[regex]::Replace($content,'(?m)^\s*Listen\s+\d+.*$',('Listen '+$port),1); $content=[regex]::Replace($content,'(?m)^\s*ServerName\s+.*$',('ServerName localhost:'+$port),1); Set-Content -LiteralPath $config -Value $content -Encoding ASCII"
if errorlevel 1 ( echo [ERRO] Nao foi possivel configurar o Apache na porta %~1. & exit /b 1 )
echo       Apache configurado automaticamente na porta %~1.
exit /b 0

:configure_app_url
powershell -NoProfile -ExecutionPolicy Bypass -Command "$envFile='%PROJECT_DIR%\.env'; $url='%APP_URL%'; $content=Get-Content -Raw -LiteralPath $envFile; if ($content -match '(?m)^APP_URL=') { $content=[regex]::Replace($content,'(?m)^APP_URL=.*$',('APP_URL='+$url),1) } else { $content += [Environment]::NewLine + 'APP_URL=' + $url }; Set-Content -LiteralPath $envFile -Value $content -Encoding utf8"
if errorlevel 1 ( echo [ERRO] Nao foi possivel atualizar APP_URL no arquivo .env. & exit /b 1 )
exit /b 0

:failure
echo.
echo A inicializacao foi interrompida.
echo Leia a mensagem [ERRO] acima, corrija a configuracao e tente novamente.
pause

:cleanup
if "%STARTED_APACHE%"=="1" taskkill /F /IM httpd.exe >nul 2>&1
if "%STARTED_MYSQL%"=="1" taskkill /F /IM mysqld.exe >nul 2>&1
echo Servicos iniciados por este script foram encerrados.
endlocal
exit /b

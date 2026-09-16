@echo off
setlocal EnableExtensions DisableDelayedExpansion
title Re.Source - Ambiente de Desenvolvimento Local
color 0A

rem Edite apenas estas variaveis se o XAMPP ou o repositorio estiverem em outro local.
set "PROJECT_NAME=re.source"
set "REPOSITORY_URL=https://github.com/ddsc-sts/re.source.git"
set "XAMPP_DIR=C:\xampp"
set "HTDOCS_DIR=%XAMPP_DIR%\htdocs"
set "PROJECT_DIR=%HTDOCS_DIR%\%PROJECT_NAME%"
set "MYSQLD_EXE=%XAMPP_DIR%\mysql\bin\mysqld.exe"
set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
set "MYSQLADMIN_EXE=%XAMPP_DIR%\mysql\bin\mysqladmin.exe"
set "HTTPD_EXE=%XAMPP_DIR%\apache\bin\httpd.exe"
set "APP_URL=http://localhost/%PROJECT_NAME%"
set "STARTED_MYSQL=0"
set "STARTED_APACHE=0"

echo ==========================================
echo       INICIANDO AMBIENTE RE.SOURCE
echo ==========================================
echo.

if not exist "%XAMPP_DIR%\" (
    echo [ERRO] XAMPP nao encontrado em "%XAMPP_DIR%".
    goto :failure
)

if not exist "%MYSQLD_EXE%" (
    echo [ERRO] mysqld.exe nao encontrado.
    goto :failure
)

if not exist "%HTTPD_EXE%" (
    echo [ERRO] httpd.exe nao encontrado.
    goto :failure
)

echo [1/5] Verificando o projeto em htdocs...
if not exist "%PROJECT_DIR%\" (
    where git >nul 2>&1
    if errorlevel 1 (
        echo [ERRO] Git nao foi encontrado. Instale o Git e execute novamente.
        goto :failure
    )

    echo       Clonando %REPOSITORY_URL%...
    git clone "%REPOSITORY_URL%" "%PROJECT_DIR%"
    if errorlevel 1 (
        echo [ERRO] Nao foi possivel clonar o repositorio.
        goto :failure
    )
) else (
    echo       Projeto ja existe; o clone foi mantido.
)

if not exist "%PROJECT_DIR%\.env" (
    if exist "%PROJECT_DIR%\.env.example" (
        copy /Y "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
        echo       Arquivo .env criado com a configuracao local padrao.
    ) else (
        echo [ERRO] .env.example nao foi encontrado no projeto.
        goto :failure
    )
)

echo [2/5] Iniciando MySQL...
"%MYSQLADMIN_EXE%" --host=127.0.0.1 --port=3306 --user=root ping --silent >nul 2>&1
if errorlevel 1 (
    start "Re.Source MySQL" /MIN "%MYSQLD_EXE%"
    set "STARTED_MYSQL=1"
    call :wait_for_mysql
    if errorlevel 1 goto :failure
) else (
    echo       MySQL ja estava em execucao.
)

echo [3/5] Importando schema e dados de demonstracao...
call :import_sql "%PROJECT_DIR%\database\seeders\re.sourcebanco.sql"
if errorlevel 1 goto :failure
call :import_sql "%PROJECT_DIR%\database\inserts\create_admin.sql"
if errorlevel 1 goto :failure
call :import_sql "%PROJECT_DIR%\database\inserts\empresa_demo.sql"
if errorlevel 1 goto :failure
call :import_sql "%PROJECT_DIR%\database\inserts\produto.sql"
if errorlevel 1 goto :failure
call :import_sql "%PROJECT_DIR%\database\inserts\saldo_demo.sql"
if errorlevel 1 goto :failure

echo [4/5] Iniciando Apache...
netstat -ano | findstr /R /C:":80 .*LISTENING" >nul
if errorlevel 1 (
    "%HTTPD_EXE%" -t >nul 2>&1
    if errorlevel 1 (
        echo [ERRO] A configuracao do Apache e invalida. Verifique "%XAMPP_DIR%\apache\conf\httpd.conf".
        goto :failure
    )
    start "Re.Source Apache" /MIN "%HTTPD_EXE%"
    set "STARTED_APACHE=1"
    timeout /t 3 /nobreak >nul
) else (
    echo       A porta 80 ja esta em uso; Apache nao sera iniciado por este script.
)

rem O monitor embutido garante a limpeza mesmo quando a janela for fechada pelo botao X.
for /f %%P in ('powershell -NoProfile -Command "(Get-CimInstance Win32_Process -Filter 'ProcessId=$PID').ParentProcessId"') do set "CMD_PID=%%P"
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
if %MYSQL_TRIES% GEQ 20 (
    echo [ERRO] MySQL nao respondeu em 20 segundos.
    exit /b 1
)
timeout /t 1 /nobreak >nul
goto :wait_for_mysql_loop

:import_sql
if not exist "%~1" (
    echo [ERRO] Arquivo SQL nao encontrado: "%~1"
    exit /b 1
)
echo       Importando %~nx1
"%MYSQL_EXE%" --host=127.0.0.1 --port=3306 --user=root --default-character-set=utf8mb4 < "%~1"
if errorlevel 1 (
    echo [ERRO] Falha ao importar %~nx1.
    exit /b 1
)
exit /b 0

:failure
echo.
echo A inicializacao foi interrompida.
goto :cleanup

:cleanup
if "%STARTED_APACHE%"=="1" taskkill /F /IM httpd.exe >nul 2>&1
if "%STARTED_MYSQL%"=="1" taskkill /F /IM mysqld.exe >nul 2>&1
echo Servicos iniciados por este script foram encerrados.
endlocal
exit /b

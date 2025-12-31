<?php declare(strict_types = 1);

// odsl-d:\GitHub\PHP-CRUD-API-Generator\src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\ApiGenerator.php' => 
    array (
      0 => '813b6841e1a858e17eefef977a19216b6ec4433a',
      1 => 
      array (
        0 => 'app\\apigenerator',
      ),
      2 => 
      array (
        0 => 'app\\__construct',
        1 => 'app\\list',
        2 => 'app\\read',
        3 => 'app\\create',
        4 => 'app\\update',
        5 => 'app\\delete',
        6 => 'app\\bulkcreate',
        7 => 'app\\bulkdelete',
        8 => 'app\\count',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Application\\HookManager.php' => 
    array (
      0 => 'cda7043e00e5cd5345a85f86e572e16d4d6e3dda',
      1 => 
      array (
        0 => 'app\\application\\hookmanager',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Application\\Router.php' => 
    array (
      0 => '3bbf9008ed31f24528f36cd7c09969dad6854931',
      1 => 
      array (
        0 => 'app\\application\\router',
      ),
      2 => 
      array (
        0 => 'app\\application\\__construct',
        1 => 'app\\application\\route',
        2 => 'app\\application\\getratelimitidentifier',
        3 => 'app\\application\\getrequestheaders',
        4 => 'app\\application\\logresponse',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Auth\\Authenticator.php' => 
    array (
      0 => '0363f18defe0a57c420d501e2d2b5c6be9138d88',
      1 => 
      array (
        0 => 'app\\auth\\authenticator',
      ),
      2 => 
      array (
        0 => 'app\\auth\\__construct',
        1 => 'app\\auth\\authenticate',
        2 => 'app\\auth\\requireauth',
        3 => 'app\\auth\\createjwt',
        4 => 'app\\auth\\validatejwt',
        5 => 'app\\auth\\getheaders',
        6 => 'app\\auth\\requirebasicauth',
        7 => 'app\\auth\\getcurrentuser',
        8 => 'app\\auth\\getcurrentuserrole',
        9 => 'app\\auth\\authenticatefromdatabase',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\CacheInterface.php' => 
    array (
      0 => '3eba632755e963a11f19ad27d88f9cac5933bfaf',
      1 => 
      array (
        0 => 'app\\cache\\cacheinterface',
      ),
      2 => 
      array (
        0 => 'app\\cache\\get',
        1 => 'app\\cache\\set',
        2 => 'app\\cache\\delete',
        3 => 'app\\cache\\deletepattern',
        4 => 'app\\cache\\clear',
        5 => 'app\\cache\\has',
        6 => 'app\\cache\\getstats',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\CacheManager.php' => 
    array (
      0 => 'd37ade58985b6c3db23b7cc65c5be009f5b3661a',
      1 => 
      array (
        0 => 'app\\cache\\cachemanager',
      ),
      2 => 
      array (
        0 => 'app\\cache\\__construct',
        1 => 'app\\cache\\initializedriver',
        2 => 'app\\cache\\isenabled',
        3 => 'app\\cache\\shouldcache',
        4 => 'app\\cache\\generatekey',
        5 => 'app\\cache\\get',
        6 => 'app\\cache\\set',
        7 => 'app\\cache\\getttl',
        8 => 'app\\cache\\invalidatetable',
        9 => 'app\\cache\\delete',
        10 => 'app\\cache\\clear',
        11 => 'app\\cache\\has',
        12 => 'app\\cache\\getstats',
        13 => 'app\\cache\\gethitratio',
        14 => 'app\\cache\\getapikeyfromrequest',
        15 => 'app\\cache\\getdriver',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\Drivers\\FileCache.php' => 
    array (
      0 => 'a0660a4a7cb5110c643d5bcb8036cdcdb39506f7',
      1 => 
      array (
        0 => 'app\\cache\\drivers\\filecache',
      ),
      2 => 
      array (
        0 => 'app\\cache\\drivers\\__construct',
        1 => 'app\\cache\\drivers\\get',
        2 => 'app\\cache\\drivers\\set',
        3 => 'app\\cache\\drivers\\delete',
        4 => 'app\\cache\\drivers\\deletepattern',
        5 => 'app\\cache\\drivers\\clear',
        6 => 'app\\cache\\drivers\\has',
        7 => 'app\\cache\\drivers\\getstats',
        8 => 'app\\cache\\drivers\\getfilepath',
        9 => 'app\\cache\\drivers\\glob_recursive',
        10 => 'app\\cache\\drivers\\formatbytes',
        11 => 'app\\cache\\drivers\\cleanup',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\Drivers\\RedisCache.php' => 
    array (
      0 => 'a2e311f864b5a794d9af9a8e533717e774e0f45e',
      1 => 
      array (
        0 => 'app\\cache\\drivers\\rediscache',
      ),
      2 => 
      array (
        0 => 'app\\cache\\drivers\\__construct',
        1 => 'app\\cache\\drivers\\get',
        2 => 'app\\cache\\drivers\\set',
        3 => 'app\\cache\\drivers\\delete',
        4 => 'app\\cache\\drivers\\deletepattern',
        5 => 'app\\cache\\drivers\\clear',
        6 => 'app\\cache\\drivers\\has',
        7 => 'app\\cache\\drivers\\getstats',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\ApiConfig.php' => 
    array (
      0 => 'f5029a18d108f917451c426a251465c9e8b3b1df',
      1 => 
      array (
        0 => 'app\\config\\apiconfig',
      ),
      2 => 
      array (
        0 => 'app\\config\\__construct',
        1 => 'app\\config\\fromfile',
        2 => 'app\\config\\isauthenabled',
        3 => 'app\\config\\getauthmethod',
        4 => 'app\\config\\getapikeys',
        5 => 'app\\config\\getapikeyrole',
        6 => 'app\\config\\getbasicusers',
        7 => 'app\\config\\usedatabaseauth',
        8 => 'app\\config\\getjwtsecret',
        9 => 'app\\config\\getjwtexpiration',
        10 => 'app\\config\\getjwtalgorithm',
        11 => 'app\\config\\getroles',
        12 => 'app\\config\\getuserroles',
        13 => 'app\\config\\getuserrole',
        14 => 'app\\config\\getratelimitconfig',
        15 => 'app\\config\\getloggingconfig',
        16 => 'app\\config\\getmonitoringconfig',
        17 => 'app\\config\\ismonitoringenabled',
        18 => 'app\\config\\toarray',
        19 => 'app\\config\\enableauth',
        20 => 'app\\config\\disableauth',
        21 => 'app\\config\\setauthmethod',
        22 => 'app\\config\\addapikey',
        23 => 'app\\config\\removeapikey',
        24 => 'app\\config\\addbasicuser',
        25 => 'app\\config\\removebasicuser',
        26 => 'app\\config\\setjwtsecret',
        27 => 'app\\config\\assignuserrole',
        28 => 'app\\config\\removeuserrole',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\CacheConfig.php' => 
    array (
      0 => '7cea1381185e3f9e707357f132d1708c2010fdde',
      1 => 
      array (
        0 => 'app\\config\\cacheconfig',
      ),
      2 => 
      array (
        0 => 'app\\config\\__construct',
        1 => 'app\\config\\fromfile',
        2 => 'app\\config\\isenabled',
        3 => 'app\\config\\getdriver',
        4 => 'app\\config\\getdefaultttl',
        5 => 'app\\config\\gettablettl',
        6 => 'app\\config\\shouldcachetable',
        7 => 'app\\config\\getvaryby',
        8 => 'app\\config\\getcachepath',
        9 => 'app\\config\\getalltablettl',
        10 => 'app\\config\\getexcludedtables',
        11 => 'app\\config\\toarray',
        12 => 'app\\config\\enable',
        13 => 'app\\config\\disable',
        14 => 'app\\config\\setdriver',
        15 => 'app\\config\\setdefaultttl',
        16 => 'app\\config\\settablettl',
        17 => 'app\\config\\excludetable',
        18 => 'app\\config\\includetable',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Database.php' => 
    array (
      0 => 'ee46b073e9f59f4837fa0f9a7a0be8af62c290e6',
      1 => 
      array (
        0 => 'app\\database\\database',
      ),
      2 => 
      array (
        0 => 'app\\database\\__construct',
        1 => 'app\\database\\getpdo',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\DialectInterface.php' => 
    array (
      0 => 'b2afe8048d00826d1fa0570d01eb64f8f3f900c9',
      1 => 
      array (
        0 => 'app\\database\\dialect\\dialectinterface',
      ),
      2 => 
      array (
        0 => 'app\\database\\dialect\\quoteident',
        1 => 'app\\database\\dialect\\listtables',
        2 => 'app\\database\\dialect\\listcolumns',
        3 => 'app\\database\\dialect\\getprimarykey',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\MySqlDialect.php' => 
    array (
      0 => '695a2b8f717310dbf3360128b774f4cdf118477c',
      1 => 
      array (
        0 => 'app\\database\\dialect\\mysqldialect',
      ),
      2 => 
      array (
        0 => 'app\\database\\dialect\\quoteident',
        1 => 'app\\database\\dialect\\listtables',
        2 => 'app\\database\\dialect\\listcolumns',
        3 => 'app\\database\\dialect\\getprimarykey',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\PostgresDialect.php' => 
    array (
      0 => '03b4c64ba41a2a4ec84b27dd0a8d24f0f63a1963',
      1 => 
      array (
        0 => 'app\\database\\dialect\\postgresdialect',
      ),
      2 => 
      array (
        0 => 'app\\database\\dialect\\quoteident',
        1 => 'app\\database\\dialect\\listtables',
        2 => 'app\\database\\dialect\\listcolumns',
        3 => 'app\\database\\dialect\\getprimarykey',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\SchemaInspector.php' => 
    array (
      0 => '294a2f1eefa400fa2719f5cca7323419ae7f1669',
      1 => 
      array (
        0 => 'app\\database\\schemainspector',
      ),
      2 => 
      array (
        0 => 'app\\database\\__construct',
        1 => 'app\\database\\gettables',
        2 => 'app\\database\\getcolumns',
        3 => 'app\\database\\getprimarykey',
        4 => 'app\\database\\quoteident',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Docs\\OpenApiGenerator.php' => 
    array (
      0 => '2f5c1ba572dfd4af5e3f33716981eb31c8fb05a8',
      1 => 
      array (
        0 => 'app\\docs\\openapigenerator',
      ),
      2 => 
      array (
        0 => 'app\\docs\\generate',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\HookManager.php' => 
    array (
      0 => 'f35065a50cf3641a0cc6e20855493f21aa0e8461',
      1 => 
      array (
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Action.php' => 
    array (
      0 => '6ceefc600fba0e70666428f393c1b859ee11d9b8',
      1 => 
      array (
        0 => 'app\\http\\action',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\ApiController.php' => 
    array (
      0 => '59151042bf73e4b40cc66420eac2b35a6834dc3d',
      1 => 
      array (
        0 => 'app\\http\\controllers\\apicontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\tables',
        2 => 'app\\http\\controllers\\columns',
        3 => 'app\\http\\controllers\\list',
        4 => 'app\\http\\controllers\\count',
        5 => 'app\\http\\controllers\\read',
        6 => 'app\\http\\controllers\\create',
        7 => 'app\\http\\controllers\\update',
        8 => 'app\\http\\controllers\\delete',
        9 => 'app\\http\\controllers\\bulkcreate',
        10 => 'app\\http\\controllers\\bulkdelete',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\DocsController.php' => 
    array (
      0 => 'e54ca66784b5db34c729454defe1aaac810ce8cd',
      1 => 
      array (
        0 => 'app\\http\\controllers\\docscontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\openapi',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\LoginController.php' => 
    array (
      0 => '04dbdb7acc448b2defbde373f5301e0cbda49236',
      1 => 
      array (
        0 => 'app\\http\\controllers\\logincontroller',
      ),
      2 => 
      array (
        0 => 'app\\http\\controllers\\__construct',
        1 => 'app\\http\\controllers\\handle',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\ErrorResponder.php' => 
    array (
      0 => '6848a01e6197bbde51f98a4ff6b9287d03786a60',
      1 => 
      array (
        0 => 'app\\http\\errorresponder',
      ),
      2 => 
      array (
        0 => 'app\\http\\__construct',
        1 => 'app\\http\\fromexception',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Middleware\\CorsMiddleware.php' => 
    array (
      0 => '37635b67393f63ebe0f48a71460002845984a636',
      1 => 
      array (
        0 => 'app\\http\\middleware\\corsmiddleware',
      ),
      2 => 
      array (
        0 => 'app\\http\\middleware\\__construct',
        1 => 'app\\http\\middleware\\apply',
        2 => 'app\\http\\middleware\\handlepreflight',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Middleware\\RateLimitMiddleware.php' => 
    array (
      0 => '09606e124433acd27c3c07aceb351219aa1873e9',
      1 => 
      array (
        0 => 'app\\http\\middleware\\ratelimitmiddleware',
      ),
      2 => 
      array (
        0 => 'app\\http\\middleware\\__construct',
        1 => 'app\\http\\middleware\\checkandrespond',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Response.php' => 
    array (
      0 => '42fc7c327c10ad399d71f5e7e94207ca1f3ddcf6',
      1 => 
      array (
        0 => 'app\\http\\response',
      ),
      2 => 
      array (
        0 => 'app\\http\\json',
        1 => 'app\\http\\error',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Observability\\Monitor.php' => 
    array (
      0 => 'eafed125a4608bd5212c89e51059998e0e5ed5f0',
      1 => 
      array (
        0 => 'app\\observability\\monitor',
      ),
      2 => 
      array (
        0 => 'app\\observability\\__construct',
        1 => 'app\\observability\\ensuredirectories',
        2 => 'app\\observability\\recordmetric',
        3 => 'app\\observability\\recordrequest',
        4 => 'app\\observability\\recordresponse',
        5 => 'app\\observability\\recorderror',
        6 => 'app\\observability\\recordsecurityevent',
        7 => 'app\\observability\\gethealthstatus',
        8 => 'app\\observability\\getstats',
        9 => 'app\\observability\\getsystemmetrics',
        10 => 'app\\observability\\getuptime',
        11 => 'app\\observability\\triggeralert',
        12 => 'app\\observability\\getrecentalerts',
        13 => 'app\\observability\\getrecentauthfailures',
        14 => 'app\\observability\\writemetric',
        15 => 'app\\observability\\writealert',
        16 => 'app\\observability\\getmetricsfile',
        17 => 'app\\observability\\getalertsfile',
        18 => 'app\\observability\\getemptystats',
        19 => 'app\\observability\\cleanup',
        20 => 'app\\observability\\exportmetrics',
        21 => 'app\\observability\\exportprometheus',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Observability\\RequestLogger.php' => 
    array (
      0 => '1cc2773bc41e26b41d64b224c129296634a8180a',
      1 => 
      array (
        0 => 'app\\observability\\requestlogger',
      ),
      2 => 
      array (
        0 => 'app\\observability\\__construct',
        1 => 'app\\observability\\logrequest',
        2 => 'app\\observability\\logauth',
        3 => 'app\\observability\\logerror',
        4 => 'app\\observability\\logratelimit',
        5 => 'app\\observability\\logquickrequest',
        6 => 'app\\observability\\getstats',
        7 => 'app\\observability\\cleanup',
        8 => 'app\\observability\\redactsensitive',
        9 => 'app\\observability\\levelfromstatus',
        10 => 'app\\observability\\writeline',
        11 => 'app\\observability\\currentlogfile',
        12 => 'app\\observability\\mayberotate',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\RateLimiter.php' => 
    array (
      0 => '807c17c24bcea493e3008fa3b7a620995e4953ea',
      1 => 
      array (
        0 => 'app\\security\\ratelimiter',
      ),
      2 => 
      array (
        0 => 'app\\security\\__construct',
        1 => 'app\\security\\checklimit',
        2 => 'app\\security\\getrequestcount',
        3 => 'app\\security\\getremainingrequests',
        4 => 'app\\security\\getresettime',
        5 => 'app\\security\\reset',
        6 => 'app\\security\\getheaders',
        7 => 'app\\security\\sendratelimitresponse',
        8 => 'app\\security\\cleanup',
        9 => 'app\\security\\getrequests',
        10 => 'app\\security\\saverequests',
        11 => 'app\\security\\getstoragefile',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\Rbac.php' => 
    array (
      0 => 'b4864bb1f5f3e0b3005d45e57349b4bfec841964',
      1 => 
      array (
        0 => 'app\\security\\rbac',
      ),
      2 => 
      array (
        0 => 'app\\security\\__construct',
        1 => 'app\\security\\isallowed',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\RbacGuard.php' => 
    array (
      0 => '26c9de014d37b9e0d29bc2190545d6f33e4feafa',
      1 => 
      array (
        0 => 'app\\security\\rbacguard',
      ),
      2 => 
      array (
        0 => 'app\\security\\__construct',
        1 => 'app\\security\\guard',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Support\\QueryValidator.php' => 
    array (
      0 => 'b74f9a4df607d29cdb0f2661de0697f984f883ca',
      1 => 
      array (
        0 => 'app\\support\\queryvalidator',
      ),
      2 => 
      array (
        0 => 'app\\support\\table',
        1 => 'app\\support\\id',
        2 => 'app\\support\\page',
        3 => 'app\\support\\pagesize',
        4 => 'app\\support\\sort',
      ),
      3 => 
      array (
      ),
    ),
    'd:\\GitHub\\PHP-CRUD-API-Generator\\src\\Support\\Validator.php' => 
    array (
      0 => '1db820fdb3724ff917bd967f02721ed6791e3041',
      1 => 
      array (
        0 => 'app\\support\\validator',
      ),
      2 => 
      array (
        0 => 'app\\support\\validatetablename',
        1 => 'app\\support\\validatecolumnname',
        2 => 'app\\support\\validatepage',
        3 => 'app\\support\\validatepagesize',
        4 => 'app\\support\\validateid',
        5 => 'app\\support\\validateoperator',
        6 => 'app\\support\\sanitizefields',
        7 => 'app\\support\\validatesort',
      ),
      3 => 
      array (
      ),
    ),
  ),
));
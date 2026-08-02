<?php declare(strict_types = 1);

// odsl-D:\GitHub\PHP-CRUD-API-Generator\src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\ApiGenerator.php' => 
    array (
      0 => 'a9fc80006eed26fa6b986049e233c3c9400317ac',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Application\\HookManager.php' => 
    array (
      0 => 'd1e53826269d9f63b133640cf1b532a5537c9d92',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Application\\Router.php' => 
    array (
      0 => '5fff6a77463c320c5384ff43cd347505eb42e5e1',
      1 => 
      array (
        0 => 'app\\application\\router',
      ),
      2 => 
      array (
        0 => 'app\\application\\__construct',
        1 => 'app\\application\\route',
        2 => 'app\\application\\apicontroller',
        3 => 'app\\application\\requirepost',
        4 => 'app\\application\\readbody',
        5 => 'app\\application\\getratelimitidentifier',
        6 => 'app\\application\\getrequestheaders',
        7 => 'app\\application\\logresponse',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Auth\\Authenticator.php' => 
    array (
      0 => '9681d0976be89d5be63d6ef024f70d895cd247c8',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\CacheInterface.php' => 
    array (
      0 => '3457fddfddfe7a317d367263af5726759322c1f7',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\CacheManager.php' => 
    array (
      0 => 'e92cef394efa74e14a209ff586ea590cd11362b1',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\Drivers\\FileCache.php' => 
    array (
      0 => 'a6bc20bc17d03ea2609bde500d49769c68482b67',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Cache\\Drivers\\RedisCache.php' => 
    array (
      0 => '8c27eb773e5007cb2d47f3cea3f06ee6eafb2224',
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
        8 => 'app\\cache\\drivers\\getconfig',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\ApiConfig.php' => 
    array (
      0 => '478ad6389effd5b601510e35fa1cf4f0805e7e09',
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
        12 => 'app\\config\\getallowedtables',
        13 => 'app\\config\\getdeniedtables',
        14 => 'app\\config\\gettablepolicy',
        15 => 'app\\config\\getuserroles',
        16 => 'app\\config\\getuserrole',
        17 => 'app\\config\\getratelimitconfig',
        18 => 'app\\config\\getloggingconfig',
        19 => 'app\\config\\getmonitoringconfig',
        20 => 'app\\config\\ismonitoringenabled',
        21 => 'app\\config\\toarray',
        22 => 'app\\config\\enableauth',
        23 => 'app\\config\\disableauth',
        24 => 'app\\config\\setauthmethod',
        25 => 'app\\config\\addapikey',
        26 => 'app\\config\\removeapikey',
        27 => 'app\\config\\addbasicuser',
        28 => 'app\\config\\removebasicuser',
        29 => 'app\\config\\setjwtsecret',
        30 => 'app\\config\\assignuserrole',
        31 => 'app\\config\\removeuserrole',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\CacheConfig.php' => 
    array (
      0 => '19f62587a4ff0271934c6e75e64a22b106358015',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Database.php' => 
    array (
      0 => '18f63f2486355a97f23d945f8cb71e052c03a427',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\DialectInterface.php' => 
    array (
      0 => '244745ab27ee71b4c145119d937611d2b7c83259',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\MySqlDialect.php' => 
    array (
      0 => '495562d9b9d17d2f9dbfc6d84b85deeb2255e6bf',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\Dialect\\PostgresDialect.php' => 
    array (
      0 => '855d9ecec956e585b57edd65d5b2b64b6e3bcc2d',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Database\\SchemaInspector.php' => 
    array (
      0 => '60bf805f8d2008bdbded1659dc45c32147b4440d',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Docs\\OpenApiGenerator.php' => 
    array (
      0 => 'a80c206ee76681feac669c27b7d27cecedb1620f',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\HookManager.php' => 
    array (
      0 => '3edfbd9f6ac64c3f95605968dbe18de3a1a04bae',
      1 => 
      array (
        0 => 'app\\hookmanager',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Action.php' => 
    array (
      0 => 'bf75e0d16fb9d71cc917f5b6f3215e45bfefbd22',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\ApiController.php' => 
    array (
      0 => 'c99ce794724b5406a3e3e6d510e3380f30d89b7d',
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
        11 => 'app\\http\\controllers\\denyiftableblocked',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\DocsController.php' => 
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Controllers\\LoginController.php' => 
    array (
      0 => '61e8f3ae1ce6c796e79704537380dfedbccfe335',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\ErrorResponder.php' => 
    array (
      0 => '88cc56a32bed714f2d8136c38b883d6cb6925abe',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Middleware\\CorsMiddleware.php' => 
    array (
      0 => 'ca691857129616f45e94b46270259556ca197461',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Middleware\\RateLimitMiddleware.php' => 
    array (
      0 => 'b1e3f80ee47c34f3e0387aa372cc05abc5cb9368',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Http\\Response.php' => 
    array (
      0 => '18518c0cb52bf4c6e4b7a4f87cd7160197fcb947',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Observability\\Monitor.php' => 
    array (
      0 => '62e654ffd0d895b698ac15c4d19a7c875a61b4b8',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Observability\\RequestLogger.php' => 
    array (
      0 => 'ad7a7d10b3af78bdc765d6d2105f968ab29f4613',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\RateLimiter.php' => 
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\Rbac.php' => 
    array (
      0 => '922749b5ff1dfac0d5b681ad026578acb281ae9d',
      1 => 
      array (
        0 => 'app\\security\\rbac',
      ),
      2 => 
      array (
        0 => 'app\\security\\__construct',
        1 => 'app\\security\\isallowed',
        2 => 'app\\security\\filtervisibletables',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\RbacGuard.php' => 
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Support\\QueryValidator.php' => 
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Support\\Validator.php' => 
    array (
      0 => '9c0e14f7b6b3188c5a7debfce287da1730ddd240',
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
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\Env.php' => 
    array (
      0 => '007bc22224685c98905e6becafa20c2d0e4dd5ef',
      1 => 
      array (
        0 => 'app\\config\\env',
      ),
      2 => 
      array (
        0 => 'app\\config\\load',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Config\\ConfigPaths.php' => 
    array (
      0 => '46f3adeeeca3c44949aa4fcb05a2dbd83b4cb296',
      1 => 
      array (
        0 => 'app\\config\\configpaths',
      ),
      2 => 
      array (
        0 => 'app\\config\\resolvedir',
        1 => 'app\\config\\file',
        2 => 'app\\config\\api',
        3 => 'app\\config\\db',
        4 => 'app\\config\\cache',
      ),
      3 => 
      array (
      ),
    ),
    'D:\\GitHub\\PHP-CRUD-API-Generator\\src\\Security\\TablePolicy.php' => 
    array (
      0 => 'de33d68a85047832985e18f90082af5c707947d1',
      1 => 
      array (
        0 => 'app\\security\\tablepolicy',
      ),
      2 => 
      array (
        0 => 'app\\security\\__construct',
        1 => 'app\\security\\isallowed',
        2 => 'app\\security\\filter',
        3 => 'app\\security\\getallowed',
        4 => 'app\\security\\getdenied',
        5 => 'app\\security\\hasallowlist',
      ),
      3 => 
      array (
      ),
    ),
  ),
));
# Example

Success:

```
% php report.php
Array
(
    [status] => 1
    [error] => 
    [info] => Array
        (
            [0] => Array
                (
                    [apiResult] => {"status":"success","tracked":1,"invalid":0}
                )

        )

)
```

Failure:

```
% php report.php
Array
(
    [status] => 
    [error] => -3
    [info] => Array
        (
        )

)
```

---

# lots of Requests / Bulk API usage

```
require 'report.php';

print_r(matomoPageview(
	[
		[
			'url' => 'http://localhost/page1',
			'action_name' => 'PageView 01 - Title',
			'uid' => '123456789012',
			//'cip' => getUserIP(),
		],
		[
			'url' => 'http://localhost/page2',
			'action_name' => 'PageView 02 - Title',
			'uid' => '123456789012',
			//'cip' => getUserIP(),
		],
		[
			'url' => 'http://localhost/page3',
			'action_name' => 'PageView 03 - Title',
			'uid' => '123456789012',
			//'cip' => getUserIP(),
			'e_c' => 'Service',
			'e_a' => 'Auth',
			'e_n' => 'Login',
		],

	]
	,'idSite'
	,'MatomoAPI'
));
```

Result:

```
% php test.php
Array
(
    [status] => 1
    [error] => 
    [info] => Array
        (
            [0] => Array
                (
                    [apiResult] => {"status":"success","tracked":3,"invalid":0}
                )

        )

)
```

# Query report

```
% cat test.php
require 'report.php';

print_r(matomoQueryReport(
        [   
                'idSite' => 1,
                'period' => 'day',
                'date' => '2023-10-01,2023-10-03',
                'method' => 'VisitsSummary.get',
        ]   
        , 'https://your-matomo.example.com/'
        , 'access_token'
));

% php test.php
Array
(
    [status] => 1
    [data] => Array
        (
            [0] => Array
                (
                    [2023-10-01] => Array
                        (
                        )

                    [2023-10-02] => Array
                        (
                        )

                    [2023-10-03] => Array
                        (
                            [nb_uniq_visitors] => 1
                            [nb_users] => 1
                            [nb_visits] => 1
                            [nb_actions] => 2
                            [nb_visits_converted] => 0
                            [bounce_count] => 0
                            [sum_visit_length] => 1
                            [max_actions] => 2
                            [bounce_rate] => 0%
                            [nb_actions_per_visit] => 2
                            [avg_time_on_site] => 1
                        )

                )

        )

    [error] => 
    [info] => Array
        (
        )

)
```

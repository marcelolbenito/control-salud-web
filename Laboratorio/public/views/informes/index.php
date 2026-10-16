<?php

use App\Helpers\LabUrl;

header('Location: ' . LabUrl::path('/historial'), true, 302);
exit;

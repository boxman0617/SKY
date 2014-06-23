<?php
Event::SubscribeActionHook('/Route/after/Follow/HomeRequest/', array('Plugin_Test', 'AddSomething'));
Event::SubscribeActionHook('/Route/after/Follow/DirectMatchRequest/', array('Plugin_Test', 'AddSomething'));
Event::SubscribeActionHook('/Route/after/Follow/IndirectMatchRequest/', array('Plugin_Test', 'AddSomething'));
Event::SubscribeActionHook('/Route/before/Follow/HomeRequest/', array('Plugin_Test', 'IncludeCSS'));
Event::SubscribeActionHook('/Route/before/Follow/DirectMatchRequest/', array('Plugin_Test', 'IncludeCSS'));
Event::SubscribeActionHook('/Route/before/Follow/IndirectMatchRequest/', array('Plugin_Test', 'IncludeCSS'));

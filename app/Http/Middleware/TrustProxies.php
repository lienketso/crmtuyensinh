<?php
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Tin tưởng tất cả proxy (hoặc giới hạn theo IP riêng của bạn).
     */
    protected $proxies = '*';

    /**
     * Dùng toàn bộ header X-Forwarded-* để phát hiện scheme/host thật.
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
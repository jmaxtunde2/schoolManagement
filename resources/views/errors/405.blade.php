@include('errors.minimal', ['status' => 405] + \App\Support\ErrorPage::meta(405))

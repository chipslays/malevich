@props(['checked' => false])

@directive('checked', [
    'true' => 'bg-green-500',
    'false' => 'bg-gray-200',
])

<span @ui></span>

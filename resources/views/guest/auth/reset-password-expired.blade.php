<main class="mt-8 bg-white rounded-lg overflow-hidden border-[#969696] border-2 p-8 px-12">
    <h3 class="text-2xl text-[#02B3DF] mb-4">Reset Password link expired</h3>
    <p class="text-gray-600 mb-6">
        The link is invalid or has expired. Please request a new password reset link. 
        <div class="flex w-full items-center justify-between mt-4">
            <a href="{{ route('password.request') }}" class="text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Reset Password
            </a>
        </div>   
    </p>
</main>

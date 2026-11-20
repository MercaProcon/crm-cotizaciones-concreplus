<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-procon border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-procon-dark focus:bg-procon-dark active:bg-procon-dark focus:outline-none focus:ring-2 focus:ring-procon focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

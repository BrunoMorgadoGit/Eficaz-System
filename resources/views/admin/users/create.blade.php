<x-layouts.app title="Adicionar usuário | Administração" active-nav="users">
    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Administração de acessos</p>
            <h1 class="page-title">Adicionar usuário</h1>
            <p class="page-subtitle">Informe os dados do usuário e escolha o perfil que ele usará no portal.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Voltar para usuários</a>
    </section>

    <form method="POST" action="{{ route('admin.users.store') }}" class="panel mt-6 grid gap-5 p-5 sm:p-7">
        @csrf

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="field-label">Nome do usuário</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" class="field-control" autocomplete="name" maxlength="255" required>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="field-label">E-mail de acesso</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="field-control" autocomplete="email" maxlength="255" required>
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="role" class="field-label">Cargo / perfil</label>
                <select id="role" name="role" class="field-control" required>
                    <option value="">Selecione o perfil</option>
                    <option value="ADMIN" @selected(old('role') === 'ADMIN')>Administrador</option>
                    <option value="REVENDEDOR" @selected(old('role', 'REVENDEDOR') === 'REVENDEDOR')>Revendedor</option>
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div id="company-field" @class(['hidden' => old('role', 'REVENDEDOR') !== 'REVENDEDOR'])>
                <label for="company_name" class="field-label">Empresa do revendedor</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="field-control" autocomplete="organization" maxlength="255">
                <p class="field-help">O perfil comercial começará como STANDARD, sem limite de crédito definido.</p>
                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-brand">Adicionar usuário</button>
        </div>
    </form>

    <script>
        (function () {
            const role = document.getElementById('role');
            const companyField = document.getElementById('company-field');
            const companyName = document.getElementById('company_name');

            if (!role || !companyField || !companyName) return;

            function updateCompanyField() {
                const isReseller = role.value === 'REVENDEDOR';
                companyField.classList.toggle('hidden', !isReseller);
                companyName.required = isReseller;
            }

            role.addEventListener('change', updateCompanyField);
            updateCompanyField();
        })();
    </script>
</x-layouts.app>

<x-layouts.app title="Usuários | Administração" active-nav="users">
    <section class="page-heading module-page-heading">
        <div>
            <p class="eyebrow">Administração de acessos</p>
            <h1 class="page-title">Usuários</h1>
            <p class="page-subtitle">Cadastre e mantenha os acessos de administradores e revendedores.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-brand">
            <x-icon name="plus" size="1em" />
            Adicionar usuário
        </a>
    </section>

    <section class="panel mt-6" aria-label="Usuários cadastrados">
        @if ($users->count())
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Nome</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Cargo</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="font-medium text-navy-950">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user()))
                                        <span class="ml-1 text-xs font-normal text-slate-500">(você)</span>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->isAdmin() ? 'Administrador' : 'Revendedor' }}</td>
                                <td><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" /></td>
                                <td>
                                    <div class="flex min-w-52 flex-wrap justify-end gap-2">
                                        @if ($user->is_active)
                                            <form method="POST" action="{{ route('admin.users.password.send', $user) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary min-h-9 px-3 py-1.5 text-xs">
                                                    Enviar acesso
                                                </button>
                                            </form>
                                        @endif

                                        @if (! $user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.status.update', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                                <button type="submit" @class([
                                                    'btn min-h-9 px-3 py-1.5 text-xs',
                                                    'btn-danger' => $user->is_active,
                                                    'btn-secondary' => ! $user->is_active,
                                                ])>
                                                    {{ $user->is_active ? 'Inativar' : 'Reativar' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state
                title="Nenhum usuário cadastrado"
                description="Adicione um administrador ou revendedor para liberar o acesso ao portal."
                action-label="Adicionar usuário"
                :action-route="route('admin.users.create')"
            />
        @endif
    </section>

    @if ($users->hasPages())
        <div class="mt-6">{{ $users->links() }}</div>
    @endif
</x-layouts.app>

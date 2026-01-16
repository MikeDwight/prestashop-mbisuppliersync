<div class="panel">
  <h3>MBI — Supplier Sync</h3>

  <form method="post" style="margin-bottom: 15px;">
  <button
    type="submit"
    name="submitMbiSupplierSyncRun"
    class="btn btn-primary"
  >
    Lancer une synchronisation (simulation)
  </button>
</form>

  <p>
    Cette page affichera l’historique des runs (Étape 1 : squelette).
  </p>

  <p>
    <strong>Configurer le module :</strong>
    <a href="{$mbiss_configure_url|escape:'htmlall':'UTF-8'}">ouvrir la configuration</a>
  </p>

  <p>
    <strong>Cron URL :</strong><br>
    <code>{$mbiss_cron_url|escape:'htmlall':'UTF-8'}</code>
  </p>

  <hr>

<h4>Historique des synchronisations</h4>



{if isset($mbiss_runs) && $mbiss_runs|count > 0}
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Run #</th>
          <th>Début</th>
          <th>Fin</th>
          <th>Statut</th>
          <th>Total</th>
          <th>Maj</th>
          <th>Erreurs</th>
          <th>Durée</th>
          <th>Message</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$mbiss_runs item=run}
          <tr>
            <td>
              <a href="{$currentIndex|escape:'htmlall':'UTF-8'}&token={$token|escape:'htmlall':'UTF-8'}&id_run={$run.id_run}">
                {$run.id_run}
              </a>
            </td>
            <td>{$run.started_at|escape:'htmlall':'UTF-8'}</td>
            <td>{if $run.ended_at}{$run.ended_at|escape:'htmlall':'UTF-8'}{else}—{/if}</td>
            <td>{$run.status|escape:'htmlall':'UTF-8'}</td>
            <td>{$run.items_total}</td>
            <td>{$run.items_updated}</td>
            <td>{$run.items_failed}</td>
            <td>{if $run.execution_ms}{$run.execution_ms} ms{else}—{/if}</td>
            <td>{$run.message|escape:'htmlall':'UTF-8'}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  </div>
{else}
  <p class="text-muted">Aucun run pour le moment.</p>
{/if}

{if $mbiss_selected_run}
  <hr>
  <h4>Détail du run #{$mbiss_selected_run.id_run}</h4>

  <p>
    <strong>Début :</strong> {$mbiss_selected_run.started_at|escape:'htmlall':'UTF-8'}
    &nbsp; | &nbsp;
    <strong>Fin :</strong> {if $mbiss_selected_run.ended_at}{$mbiss_selected_run.ended_at|escape:'htmlall':'UTF-8'}{else}—{/if}
    &nbsp; | &nbsp;
    <strong>Statut :</strong> {$mbiss_selected_run.status|escape:'htmlall':'UTF-8'}
    &nbsp; | &nbsp;
    <strong>Durée :</strong> {if $mbiss_selected_run.execution_ms}{$mbiss_selected_run.execution_ms} ms{else}—{/if}
  </p>

  <p>
    <strong>Total :</strong> {$mbiss_selected_run.items_total}
    &nbsp; | &nbsp;
    <strong>Mis à jour :</strong> {$mbiss_selected_run.items_updated}
    &nbsp; | &nbsp;
    <strong>Erreurs :</strong> {$mbiss_selected_run.items_failed}
  </p>

  {if $mbiss_selected_run.message}
    <p><strong>Message :</strong> {$mbiss_selected_run.message|escape:'htmlall':'UTF-8'}</p>
  {/if}

  <h4>Items</h4>

  {if isset($mbiss_selected_items) && $mbiss_selected_items|count > 0}
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>SKU</th>
            <th>ID Produit</th>
            <th>Stock</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Erreur</th>
            <th>Créé le</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$mbiss_selected_items item=item}
            <tr>
              <td>{$item.sku|escape:'htmlall':'UTF-8'}</td>
              <td>{if $item.id_product|intval > 0}{$item.id_product}{else}—{/if}</td>
              <td>
                {if $item.old_stock !== null || $item.new_stock !== null}
                  {$item.old_stock|default:'—'} → {$item.new_stock|default:'—'}
                {else}
                  —
                {/if}
              </td>
              <td>
                {if $item.old_price !== null || $item.new_price !== null}
                  {$item.old_price|default:'—'} → {$item.new_price|default:'—'}
                {else}
                  —
                {/if}
              </td>
              <td>{$item.status|escape:'htmlall':'UTF-8'}</td>
              <td>
                {if $item.status == 'error'}
                  {$item.error_code|escape:'htmlall':'UTF-8'} — {$item.error_message|escape:'htmlall':'UTF-8'}
                {else}
                  —
                {/if}
              </td>
              <td>{$item.created_at|escape:'htmlall':'UTF-8'}</td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  {else}
    <p class="text-muted">Aucun item pour ce run.</p>
  {/if}
{/if}


</div>

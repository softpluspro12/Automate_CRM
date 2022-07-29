<div class="container content">
    <div class="container-centering">
    <div id="login" class="panel panel-default block-center-sm">
        <div class="panel-body">
            <di>
                <p>{{message}}</p>
                <form id="login-form" onsubmit="return false;">
                    <div class="form-group">
                        <label for="field-code" >{{translate 'Code' scope='User'}}</label>
                        <input
                            type="text"
                            data-name="field-code"
                            class="form-control"
                            autocapitalize="off"
                            spellcheck="false"
                            tabindex="1"
                            autocomplete="new-password"
                            maxlength="7"
                        >
                    </div>
                    <div class="margin-top-2x">
                        <a
                            href="javascript:"
                            class="btn btn-link pull-right"
                            data-action="backToLogin"
                            tabindex="4"
                        >{{translate 'Back to login form' scope='User'}}</a>
                        <button
                            type="submit"
                            class="btn btn-primary btn-s-wide"
                            id="btn-send"
                            tabindex="2"
                        >{{translate 'Submit'}}</button>
                    </div>
                </form>
            </di>
        </div>
    </div>
    </div>
</div>
<footer>{{{footer}}}</footer>

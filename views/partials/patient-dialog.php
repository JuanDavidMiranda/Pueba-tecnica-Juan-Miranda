    <dialog id="patient-dialog" class="modal">
        <form id="patient-form" novalidate>
            <div class="modal-head">
                <h2 id="patient-title">Nuevo paciente</h2>
                <button class="icon-btn" type="button" data-close-dialog aria-label="Cerrar">×</button>
            </div>
            <p id="form-error" class="banner" hidden></p>
            <div class="form-grid">
                <div class="field">
                    <label for="nombre1">Primer nombre</label>
                    <input id="nombre1" name="nombre1" maxlength="60" autocomplete="given-name">
                    <span class="field-error" data-error-for="nombre1"></span>
                </div>
                <div class="field">
                    <label for="nombre2">Segundo nombre <span class="optional">opcional</span></label>
                    <input id="nombre2" name="nombre2" maxlength="60" autocomplete="additional-name">
                    <span class="field-error" data-error-for="nombre2"></span>
                </div>
                <div class="field">
                    <label for="apellido1">Primer apellido</label>
                    <input id="apellido1" name="apellido1" maxlength="60" autocomplete="family-name">
                    <span class="field-error" data-error-for="apellido1"></span>
                </div>
                <div class="field">
                    <label for="apellido2">Segundo apellido <span class="optional">opcional</span></label>
                    <input id="apellido2" name="apellido2" maxlength="60">
                    <span class="field-error" data-error-for="apellido2"></span>
                </div>
                <div class="field">
                    <label for="tipo_documento_id">Tipo de documento</label>
                    <select id="tipo_documento_id" name="tipo_documento_id"></select>
                    <span class="field-error" data-error-for="tipo_documento_id"></span>
                </div>
                <div class="field">
                    <label for="numero_documento">Número de documento</label>
                    <input id="numero_documento" name="numero_documento" maxlength="20" autocomplete="off">
                    <span class="field-error" data-error-for="numero_documento"></span>
                </div>
                <div class="field">
                    <label for="genero_id">Género</label>
                    <select id="genero_id" name="genero_id"></select>
                    <span class="field-error" data-error-for="genero_id"></span>
                </div>
                <div class="field">
                    <label for="correo">Correo electrónico</label>
                    <input id="correo" name="correo" type="email" maxlength="180" autocomplete="email">
                    <span class="field-error" data-error-for="correo"></span>
                </div>
                <div class="field">
                    <label for="departamento_id">Departamento</label>
                    <select id="departamento_id" name="departamento_id"></select>
                    <span class="field-error" data-error-for="departamento_id"></span>
                </div>
                <div class="field">
                    <label for="municipio_id">Municipio</label>
                    <select id="municipio_id" name="municipio_id" disabled>
                        <option value="">Seleccione un departamento</option>
                    </select>
                    <span class="field-error" data-error-for="municipio_id"></span>
                </div>
                <div class="field field-wide">
                    <label for="foto">Fotografía <span class="optional">opcional</span></label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                    <p id="foto-hint" class="hint">JPG, PNG o WEBP. Máximo 2 MB.</p>
                    <img id="foto-preview" class="preview" alt="" hidden>
                    <span class="field-error" data-error-for="foto"></span>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" type="button" data-close-dialog>Cancelar</button>
                <button id="save-patient" class="btn btn-primary" type="submit">Guardar paciente</button>
            </div>
        </form>
    </dialog>

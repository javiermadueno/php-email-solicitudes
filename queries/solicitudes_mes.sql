--use CRTM_ABONOS;
--Recogida en oficina
select a.Id, a.Tipo, a.tipo_abono, 
case when b.Estado IN ('SOLICITUD COMPLETADA', 'SOLICITUD COMPLETADA CRTM', 'PERSONALIZADA', 'ENVIADA_CORREOS', 'ENVIADA_OFICINA', 'ENVIADA_CRTM', 'ENVIADA_DIGITALIZADOR') THEN 'COMPLETADA' 
	 when b.Estado = 'SOLICITUD PRUEBAS' THEN 'PRUEBAS'
	 when b.Estado = 'NO_ENVIADA_CRTM' THEN 'COMPLETADA NO ENVIADA A PVTA'
	 when b.Estado = 'ANULADA' THEN 'ANULADA'
	 when b.Estado like '%TPV%' THEN 'ERROR TPV'
	 else 'INCOMPLETA' end as Estado, 
c.Documento_personal, a.Fecha_peticion, c.Nombre, c.Apellido1, c.Apellido2, c.Fecha_nacimiento, c.Correo_electronico, c.Telefono1, c.Telefono2, d.Denominacion, c.Tipo_via, c.Nombre_via, c.Numero, c.Portal, c.Escalera, c.Piso, c.Puerta, c.Provincia, c.Localidad, c.Codigo_postal, 
case when a.Club_amigos_transporte = 1 THEN 'Sí' else 'No' end as Club_amigos,
UPPER(c.Nombre) + ' ' + UPPER(c.Apellido1) + ' ' + UPPER(c.Apellido2) as Nombre_completo,
case when c.Tipo_via = 'cll' THEN 'C/' 
	 when c.Tipo_via = 'avd' THEN 'Avda.'
	 when c.Tipo_via = 'pza' THEN 'P/'
	 when c.Tipo_via = 'cta' THEN 'CR/'
	 when c.Tipo_via = 'cmo' THEN 'Camino'
	 when c.Tipo_via = 'rda' THEN 'Ronda'
	 when c.Tipo_via = 'pso' THEN 'P�/'
	 when c.Tipo_via = 'prq' THEN 'Parque'
	 when c.Tipo_via = 'trv' THEN 'TR/'
	 else c.Tipo_via end as via,
case when c.Tipo_via = 'cll' THEN 'C/' 
	 when c.Tipo_via = 'avd' THEN 'Avda.'
	 when c.Tipo_via = 'pza' THEN 'P/'
	 when c.Tipo_via = 'cta' THEN 'CR/'
	 when c.Tipo_via = 'cmo' THEN 'Camino'
	 when c.Tipo_via = 'rda' THEN 'Ronda'
	 when c.Tipo_via = 'pso' THEN 'Pº/'
	 when c.Tipo_via = 'prq' THEN 'Parque'
	 when c.Tipo_via = 'trv' THEN 'TR/'
	 else c.Tipo_via end 
	 + ' ' + UPPER(c.Nombre_via) + ' Nº ' + convert(nvarchar,c.Numero) +
case when c.Portal='' THEN '' 
	 else ' Portal ' + c.Portal end 
	 +
case when c.Escalera='' THEN ''
	 else ' Esc. ' + c.Escalera end
	 + ' ' +
case when c.Piso='-1' THEN 'SOTANO'
     when c.Piso='0' THEN 'BAJO'
     else c.Piso + 'º' end
     +
case when c.Puerta='' THEN ''
	 else ' ' + c.Puerta end  	 
	 as Direccion_completa,
UPPER(c.Localidad) + '    ' + c.Codigo_postal as CP_Municipio, 
case	when a.Familia_numerosa=1 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_familia, 120), 10)
		when a.Familia_numerosa=2 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_familia, 120), 10)
		else '' END as "Familia Numerosa",
case	when a.Discapacidad=1 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_discapacidad, 120), 10)
		when a.Discapacidad=2 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_discapacidad, 120), 10)
		else '' END as "Discapacidad",
a.Enviada_logista,		
'' as "Fecha de realizaci�n", '' as "Fecha de entrega", '' as "Fecha de recogida", '' as "Fecha cancelaci�n duplicidad", '' as "Fecha cancelaci�n otro canal", '' as "Fecha cancelaci�n usuario"
from t_solicitud a, t_estados_solicitud b, t_viajero c, t_punto_venta d,
(select Id_solicitud, MAX(Id) maxId
from t_estados_solicitud
group by Id_solicitud
)e
where a.Id = b.Id_solicitud
and a.Id_viajero = c.Id
and a.Id_punto_venta_recogida = d.Id
and e.maxId = b.Id
and
--todas las solicitudes en estado COMPLETA que no se han fabricado a�n y son del mes anterior)
((b.Estado IN ('SOLICITUD COMPLETADA', 'SOLICITUD COMPLETADA CRTM', 'PERSONALIZADA', 'ENVIADA_CORREOS', 'ENVIADA_OFICINA', 'ENVIADA_CRTM','NO_ENVIADA_CRTM', 'ENVIADA_DIGITALIZADOR')
and a.Fecha_entrega is null
and a.Fecha_realizacion is null
and a.Fecha_recogida is null
and a.Fecha_cancelacion_duplicidad is null
and a.Fecha_cancelacion_errNoSubsanados is null
and a.Fecha_cancelacion_tarjEnVigor is null
and a.id_usuario_digitalizador is null -- A�adido nuevo el 28/07/2014 para exculuir las realizadas con digitalizador
and a.Fecha_peticion >= ? and a.Fecha_peticion < ?) -- dia, mes
or 
-- todas las solicitudes del mes en curso
(a.Fecha_peticion >= ? ))

UNION

--Env�o por correo
select a.Id, a.Tipo, a.tipo_abono,
case when b.Estado IN ('SOLICITUD COMPLETADA', 'SOLICITUD COMPLETADA CRTM', 'PERSONALIZADA', 'ENVIADA_CORREOS', 'ENVIADA_OFICINA', 'ENVIADA_CRTM', 'ENVIADA_DIGITALIZADOR') THEN 'COMPLETADA' 
	 when b.Estado = 'SOLICITUD PRUEBAS' THEN 'PRUEBAS'
	 when b.Estado = 'NO_ENVIADA_CRTM' THEN 'COMPLETADA NO ENVIADA A PVTA'
	 when b.Estado = 'ANULADA' THEN 'ANULADA'
	 when b.Estado like '%TPV%' THEN 'ERROR TPV'
	 else 'INCOMPLETA' end, 
c.Documento_personal, a.Fecha_peticion, c.Nombre, c.Apellido1, c.Apellido2, c.Fecha_nacimiento, c.Correo_electronico, c.Telefono1, c.Telefono2, 'Correo Postal', c.Tipo_via, c.Nombre_via, c.Numero, c.Portal, c.Escalera, c.Piso, c.Puerta, c.Provincia, c.Localidad, c.Codigo_postal,
case when a.Club_amigos_transporte = 1 THEN 'Sí' else 'No' end as Club_amigos,
UPPER(c.Nombre) + ' ' + UPPER(c.Apellido1) + ' ' + UPPER(c.Apellido2) as Nombre_completo,
case when c.Tipo_via = 'cll' THEN 'C/' 
	 when c.Tipo_via = 'avd' THEN 'Avda.'
	 when c.Tipo_via = 'pza' THEN 'P/'
	 when c.Tipo_via = 'cta' THEN 'CR/'
	 when c.Tipo_via = 'cmo' THEN 'Camino'
	 when c.Tipo_via = 'rda' THEN 'Ronda'
	 when c.Tipo_via = 'pso' THEN 'Pº/'
	 when c.Tipo_via = 'prq' THEN 'Parque'
	 when c.Tipo_via = 'trv' THEN 'TR/'
	 else c.Tipo_via end as via,
case when c.Tipo_via = 'cll' THEN 'C/' 
	 when c.Tipo_via = 'avd' THEN 'Avda.'
	 when c.Tipo_via = 'pza' THEN 'P/'
	 when c.Tipo_via = 'cta' THEN 'CR/'
	 when c.Tipo_via = 'cmo' THEN 'Camino'
	 when c.Tipo_via = 'rda' THEN 'Ronda'
	 when c.Tipo_via = 'pso' THEN 'Pº/'
	 when c.Tipo_via = 'prq' THEN 'Parque'
	 when c.Tipo_via = 'trv' THEN 'TR/'
	 else c.Tipo_via end 
	 + ' ' + UPPER(c.Nombre_via) + ' Nº ' + convert(nvarchar,c.Numero) +
case when c.Portal='' THEN '' 
	 else ' Portal ' + c.Portal end 
	 +
case when c.Escalera='' THEN ''
	 else ' Esc. ' + c.Escalera end
	 + ' ' +
case when c.Piso='-1' THEN 'SOTANO'
     when c.Piso='0' THEN 'BAJO'
     else c.Piso + 'º' end
     +
case when c.Puerta='' THEN ''
	 else ' ' + c.Puerta end  	 
	 as Direccion_completa,
UPPER(c.Localidad) + '    ' + c.Codigo_postal as CP_Municipio, 
case	when a.Familia_numerosa=1 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_familia, 120), 10)
		when a.Familia_numerosa=2 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_familia, 120), 10)
		else '' END as "Familia Numerosa",
case	when a.Discapacidad=1 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_discapacidad, 120), 10)
		when a.Discapacidad=2 then LEFT(CONVERT(VARCHAR, a.Fecha_fin_validez_doc_discapacidad, 120), 10)
		else '' END as "Discapacidad",
a.Enviada_logista,		
'' as "Fecha de realización", '' as "Fecha de entrega", '' as "Fecha de recogida", '' as "Fecha cancelación duplicidad", '' as "Fecha cancelación otro canal", '' as "Fecha cancelaci�n usuario"
from t_solicitud a, t_estados_solicitud b, t_viajero c,
(select Id_solicitud, MAX(Id) maxId
from t_estados_solicitud
group by Id_solicitud
)e
where a.Id = b.Id_solicitud
and a.Id_viajero = c.Id
and e.maxId = b.Id
and a.Tipo_recogida = 2
and
((b.Estado IN ('SOLICITUD COMPLETADA', 'SOLICITUD COMPLETADA CRTM', 'PERSONALIZADA', 'ENVIADA_CORREOS', 'ENVIADA_OFICINA', 'ENVIADA_CRTM', 'NO_ENVIADA_CRTM', 'ENVIADA_DIGITALIZADOR')
and a.Fecha_entrega is null
and a.Fecha_realizacion is null
and a.Fecha_recogida is null
and a.Fecha_cancelacion_duplicidad is null
and a.Fecha_cancelacion_errNoSubsanados is null
and a.Fecha_cancelacion_tarjEnVigor is null
and a.id_usuario_digitalizador is null -- A�adido nuevo el 28/07/2014 para exculuir las realizadas con digitalizador
and a.Fecha_peticion >= ? and a.Fecha_peticion < ?)
or
(a.Fecha_peticion >= ?))
order by a.Id ;


